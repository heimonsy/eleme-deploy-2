<?php

use Deploy\Site\PullRequestBuild;
use Deploy\Site\Site;
use Deploy\Facade\Worker;
use Deploy\Worker\Job;
use GuzzleHttp\Client;
use Guzzle\Plugin\CurlAuth\CurlAuthPlugin;
use Deploy\Github\GithubClient;
use Symfony\Component\Yaml;

class SitePullRequestBuildController extends Controller
{
    public function index(Site $site)
    {
        return Response::json(array(
            'code' => 0,
            'data' => PullRequestBuild::of($site)->open()->orderBy('id', 'desc')->limit(30)->get()
        ));
    }

    public function store(Site $site)
    {
        if (Request::header('X-GitHub-Event') == 'pull_request') {
            $info = json_decode(file_get_contents('php://input'));
            $data = array();
            $data['pull_request_id'] = $info->pull_request->id;
            $data['commit'] = $info->pull_request->head->sha;
            $count = PullRequestBuild::where($data)->count();
            if ($count >= 1 ) {
                if ($info->action == PullRequestBuild::PR_STATUS_CLOSED) {
                    PullRequestBuild::where('pull_request_id', $data['pull_request_id'])->update(array(
                        'status' => PullRequestBuild::PR_STATUS_CLOSED,
                        //'merged_by' => $info->pull_request->merged_by->login
                    ));
                } elseif ($info->action == 'reopened') {
                    PullRequestBuild::where('pull_request_id', $data['pull_request_id'])->update(array(
                        'status' => PullRequestBuild::PR_STATUS_OPEN,
                    ));
                }
            } else {
                $job = Worker::createJob(
                    'Deploy\Worker\Jobs\BuildPullRequest',
                    "操作：PR Build &nbsp; " . "项目：{$site->name} &nbsp;" . "操作者：Hook &nbsp;",
                    array(),
                    Job::TYPE_SYSTEM
                );

                $data['site_id'] = $site->id;
                $data['job_id'] = $job->id;
                $data['title'] = $info->pull_request->title;
                $data['number'] = $info->pull_request->number;
                $data['repo_name'] = $info->pull_request->base->repo->full_name;
                $data['user_login'] = $info->pull_request->user->login;
                $data['status'] = $info->pull_request->state;
                $data['build_status'] = PullRequestBuild::STATUS_WAITING;
                $data['test_status'] = PullRequestBuild::STATUS_WAITING;
                $build = new PullRequestBuild;
                $build->fill($data);
                $build->save();

                $job->message = array('site_id' => $site->id, 'pr_id' => $build->id);
                Worker::push($job);
            }
        }
        return Response::make('ok');
    }

    public function golang()
    {
        if (Request::header('X-GitHub-Event') == 'pull_request') {
            $info = json_decode(file_get_contents('php://input'));
            if ($info->action === "opened" || $info->action === "synchronize" || $info->action === "reopen") {
                Log::info("golang ".Request::header('X-GitHub-Event'));
                App::finish(function() use($info) {
                    $number = $info->pull_request->number;
                    $repoName = $info->repository->full_name;
                    $commit = $info->pull_request->head->sha;

                    $host = Config::get("jenkins.url");
                    $job = Config::get("jenkins.jobs");
                    $token = Config::get("jenkins.token");

                    $url = "{$host}job/{$job}/buildWithParameters?token={$token}&type=pr&repo_name={$repoName}&pr_id={$number}&commit={$commit}&time=0";
                    $defaults = array(
                        'timeout' => 30,
                        'connect_timeout' => 30,
                    );
                    $client = new Client(array('defaults' => $defaults));

                    $user = Config::get("jenkins.user");
                    $resposne = "";
                    if (!empty($user)) {
                        $response = $client->get($url, ['auth' => [$user, Config::get("jenkins.pass")]]);
                    } else {
                        Log::info("golang No Auth");
                        $response = $client->get($url);
                    }
                    Log::info("golang ".$url);
                    $code = $response->getStatusCode() . '';
                    if ($code[0] != '2') {
                        Log::error("Send jenkins Request Error!!!!");
                        Log::error("$url $code ".$response->getBody());
                    }
                });
            }
        }
        if (Request::header('X-Github-Event') == 'push') {
            $info = json_decode(file_get_contents('php://input'));
            App::finish(function() use($info) {
                $commit = $info->head_commit->id;
                $createdAt = strtotime($info->head_commit->timestamp);
                $repoName = $info->repository->full_name;

                $host = Config::get("jenkins.url");
                $job = Config::get("jenkins.jobs");
                $token = Config::get("jenkins.token");

                $url = "{$host}job/{$job}/buildWithParameters?token={$token}&type=commit&repo_name={$repoName}&pr_id=0&commit={$commit}&time={$createdAt}";
                $defaults = array(
                    'timeout' => 30,
                    'connect_timeout' => 30,
                );
                $client = new Client(array('defaults' => $defaults));

                $user = Config::get("jenkins.user");
                $resposne = "";
                if (!empty($user)) {
                    $response = $client->get($url, ['auth' => [$user, Config::get("jenkins.pass")]]);
                } else {
                    $response = $client->get($url);
                }
                Log::info("golang ".$url);
                $code = $response->getStatusCode() . '';
                if ($code[0] != '2') {
                    Log::error("Send jenkins Request Error!!!!");
                    Log::error("$url $code ".$response->getBody());
                }
            });
        }
        return Response::make("OK");
    }

    public function notify() {
        $descriptions = [
            'build' => [
                "PENDING" => "Build Pending",
                "SUCCESS" => "Build Success",
                "FAILURE" => "Build Failure",
            ],
            'test' => [
                "PENDING" => "Test Pending",
                "SUCCESS" => "Test Success",
                "FAILURE" => "Test Failure",
            ],
            'lint' => [
                "PENDING" => "Golint Pending",
                "SUCCESS" => "Golint Success",
                "FAILURE" => "Golint Failure",
            ],
            'config' => [
                "FAILURE" => "goci.yml doesn't exist.",
                "ERROR" => "goci.yml parse error.",
            ],
            'gofmt' => [
                "PENDING" => "Gofmt Pending",
                "SUCCESS" => "Gofmt Success",
                "FAILURE" => "Gofmt Failure",
            ],
            'govet' => [
                "PENDING" => "Govet Pending",
                "SUCCESS" => "Govet Success",
                "FAILURE" => "Govet Failure",
            ],
            'race' => [
                "PENDING" => "Gorace Pending",
                "SUCCESS" => "Gorace Success",
                "FAILURE" => "Gorace Failure",
            ],
            'job' => [
                "WORKING" => "Job Working",
                "PENDING" => "Job Pending",
                "SUCCESS" => "Job Success",
                "FAILURE" => "Job Failure",
            ],
        ];

        $token = Input::get("token");
        if ($token != Config::get("jenkins.token")) {
            return Response::make("Token Error", 403);
        }
        $citype = Input::get("ci_type");
        $buildNumber = Input::get("build_number");
        $jobName = Input::get("job_name");
        $prNumber = Input::get("prid");
        $repoName = Input::get("repo");
        $commit = Input::get("commit");
        $result = Input::get("result");

        if ($citype == "job") {
            return Response::make("\n");
        }

        $url = Config::Get("jenkins.url")."job/$jobName/$buildNumber/console";
        $status = strtolower($result);
        $desc = $descriptions[$citype][$result];
        $context = "goci/".$citype;
        App::finish(function () use ($buildNumber, $prNumber, $repoName, $status, $commit, $url, $desc, $context){
            if ($status != "pending") {
                sleep(5);
            }
            $nCommit = substr($commit, 0, 7);
            try {
                $start = microtime(true) * 1000;
                $proxy = Config::get('github.proxy');
                $githubToken = Config::get("jenkins.github_token");
                $client = new GithubClient($githubToken, $proxy);
                $response = $client->request("repos/{$repoName}/statuses/{$commit}", json_encode(array(
                    'state' => $status,
                    "target_url" => $url,
                    "description" => $desc,
                    "context" => $context
                )), 'POST');
                $end = microtime(true) * 1000;
                if ($response == null) {
                    Log::info("[PR Notify] Response Decode Error: ".$client->getResponse()->getBody());
                } else {
                    $respContext = $response['context'];
                    $respState = $response['state'];
                    $createdAt = $response['created_at'];
                    $updatedAt = $response['updated_at'];
                    Log::info("[PR Notify] Send Success! [BN $buildNumber] [PN $prNumber] [$repoName] [$nCommit] [{$context} - {$respContext}] [$status - {$respState}] [C {$createdAt}] [U {$updatedAt}] ".floor($end-$start));
                }
            } catch (Exception $e) {
                Log::info($e);
                Log::info("[PR Notify] Send Error! [BN $buildNumber] [PN $prNumber] [$repoName] [$nCommit] [{$context}] [$status]".floor($end-$start));
            }
        });
        return Response::make("\nSend Notify \"$result\"\n");
    }
}
