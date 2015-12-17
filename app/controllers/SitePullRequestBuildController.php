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
        Log::info("golang ".Request::header('X-GitHub-Event'));
        if (Request::header('X-GitHub-Event') == 'pull_request') {
            $info = json_decode(file_get_contents('php://input'));
            if ($info->action === "opened" || $info->action === "synchronize") {
                App::finish(function() use($info) {
                    $number = $info->pull_request->number;
                    $repoName = $info->repository->full_name;
                    $commit = $info->pull_request->head->sha;

                    $host = Config::get("jenkins.url");
                    $job = Config::get("jenkins.jobs");
                    $token = Config::get("jenkins.token");

                    $url = "{$host}job/{$job}/buildWithParameters?token={$token}&repo_name={$repoName}&pr_id={$number}&commit={$commit}";
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
                        return Response::make("Send To Jenkins Error", 500);
                    }
                });
            }
        }
        return Response::make("OK");
    }

    public function notify() {
        $descriptions = [
            'build' => [
                "SUCCESS" => "Build Success",
                "FAILURE" => "Build Failure",
            ],
            'test' => [
                "SUCCESS" => "Test Success",
                "FAILURE" => "Test Failure",
            ],
            'lint' => [
                "SUCCESS" => "Golint Success",
                "FAILURE" => "Golint Failure",
            ],
            'config' => [
                "FAILURE" => "goci.yml doesn't exist.",
                "ERROR" => "goci.yml parse error.",
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
        Log::info("[PR Notify Recive] $repoName $citype $buildNumber $prNumber $commit [$result]");

        $url = Config::Get("jenkins.url")."job/$jobName/$buildNumber/console";
        $status = $result == "SUCCESS" ? "success" : "failure";
        $desc = $descriptions[$citype][$result];
        $context = "goci/".$citype;
        App::finish(function () use ($repoName, $status, $commit, $url, $desc, $context){
            try {
                $proxy = Config::get('github.proxy');
                $githubToken = Config::get("jenkins.github_token");
                $client = new GithubClient($githubToken, $proxy);

                Log::info("repos/{$repoName}/statuses/{$commit}");
                $response = $client->request("repos/{$repoName}/statuses/{$commit}", json_encode(array(
                    'state' => $status,
                    "target_url" => $url,
                    "description" => $desc,
                    "context" => $context
                )), 'POST');

                Log::info("[PR Notify Recive] Send Success! ".json_encode($response));
            } catch (Exception $e) {
                Log::info($e);
                Log::info($e->getResponse()->getBody(true));
                Log::info("Send Status Error");
            }
        });
        return Response::make("\nSend Notify OK\n");
    }
}
