<body>

<p>
    <strong>Message: </strong>
    Deploy
    <span style="color: blue;">
        <?php echo $site->name; ?>
    </span>
    <span style="color: <?php echo $status == 'Success' ? 'green' : 'red' ?>;">
        <?php echo $status; ?>
    </span>
</p>

<p>
    <strong>Hosts : </strong>
    <span><?php echo $deploy->total_hosts; ?></span>, <span style="color: green"><?php echo $deploy->success_hosts; ?></span>, <span style="color: red"><?php echo $deploy->error_hosts; ?></span>
</p>

<p>
    <strong>Job Url: </strong>
    <a href="<?php echo $jobUrl; ?>" target="_blank"><?php echo $jobUrl; ?></a>
</p>

<p>
    <strong>Operater: </strong>
    <?php echo $user->name; ?>
</p>

<p>
    <strong>Status: </strong>
    <span style="color: <?php echo $status == 'Success' ? 'green' : 'red' ?>;">
        <?php echo $status ?>
    </span>
</p>

<p>
    <strong>Commit: </strong>
    <?php echo $deploy->commit; ?>
</p>
<?php if(!empty($diffUrl)) {?>
    <p><strong>Diff: </strong><a href="<?php echo $diffUrl; ?>" target="_blank"><?php echo $diffUrl ?><a></p>
<?php }?>

</body>
