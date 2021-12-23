<?php
$rootdir = $_SERVER['DOCUMENT_ROOT'];
ini_set('include_path', "$rootdir/class");
require_once "altcosfile.php";

$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
putenv("DOCUMENT_ROOT=$DOCUMENT_ROOT");
$BINDIR = "$DOCUMENT_ROOT/ostree/bin";
$subRef = $_REQUEST['ref'];

$subVersion = repos::refVersion($subRef);
$subVersionVarSubDir = repos::versionVarSubDir($subVersion);

$altcosfile = new altcosfile($subRef);
echo "<pre>". json_encode($altcosfile, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "</pre>";
$parentRef = repos::parentRef($subRef);
echo "<pre>PARENTREF=" . print_r($parentRef, 1) . "</pre>";

$subRepo = new repo($subRef, 'bare');
$mergeDir = $subRepo->rootsDir . "/merged/";
echo "<pre>SUBTREPO=" . print_r($subRepo, 1) . "</pre>";

$parentRepo = new repo($parentRef, 'bare');
$subRefRepoDir = $parentRepo->refRepoDir;
if (!$parentRepo->haveConfig()) {
  echo "Bare repository $parentRepoBarePath don't exists";
  exit(1);
}

$commits = $parentRepo->getCommits($parentRef);
echo "<pre>PARENTREPO=" . print_r($parentRepo, 1) . "</pre>";
$lastCommitId = $parentRepo->lastCommitId;
$lastCommit = $parentRepo->lastCommit;
echo "<pre>lastCommitId=$lastCommitId lastCommit=" . print_r($lastCommit, 1) . "</pre>";
flush();

$lastVersion = $lastCommit['Version'];
$versionVarSubDir = repos::versionVarSubDir($lastVersion);
$cmd = "$BINDIR/ostree_checkout.sh '$parentRef' '$lastCommitId' '$subRef' 'all'";
echo "CHECKOUTCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "CHECKOUT=<pre>" . print_r($output, 1) . "</pre>";
flush();

$altcosfile->execActions($mergeDir, $subRef);

/*$podmanImages = implode(' ', $altcosfile->getPodmanImages());
$cmd = "$BINDIR/skopeo_copy.sh $mergeDir $podmanImages";
echo "SKOPEO_COPY_CMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "SKOPEO_COPY_OUTPUT=<pre>" . implode("\n",$output). "</pre>";
flush();
// exit(0);


$cmd = "$BINDIR/apt-get_update.sh $subRef";
echo "APT-GET_UPDATETCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "APT-GET_UPDATE=<pre>" . implode("\n",$output). "</pre>";
flush();

$rpms = implode(' ', $altcosfile->getRPMS());
$cmd = "$BINDIR/apt-get_install.sh '$subRef' $rpms";
echo "APT-GET_INSTALL=$cmd\n";
$output = [];
exec($cmd, $output);
echo "APT-GET_INSTALL=<pre>" . implode("\n",$output). "</pre>";
flush();
*/
$subRefDir = $subRepo->refDir;
$butaneFile = $altcosfile->getButaneFile();
echo "<pre>BUTANEFILE=".print_r($butaneFile, 1) . "</pre>";
$cmd = "$BINDIR/ignition.sh '$subRefDir' $butaneFile";
echo "IGNITION_CMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "IGNITION_OUTPUT=<pre>" . implode("\n",$output). "</pre>";
flush();

// exit(0);

$cmd = "$BINDIR/syncUpdates.sh $subRef $lastCommitId $subVersion";
echo "SYNCUPDATESCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "SYNCUPDATES=<pre>" . implode("\n",$output). "</pre>";
flush();

// $rpmList = $parentRepo->rpmList($subVersion);
// $subRefsConf = new refsConf($subRef, $subVersion, $pkgs);
// $subRefsConf->addRpmList($rpmList);
// $subRefsConf->save();

$cmd = "$BINDIR/ostree_commit.sh $subRef $lastCommitId $subVersion";
echo "COMMITCMD=$cmd\n";
$output = [];
exec($cmd, $output);
echo "COMMIT=<pre>" . implode("\n",$output). "</pre>";


