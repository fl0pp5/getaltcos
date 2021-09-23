<?php
$ref = $_REQUEST['ref'];
$version = $_REQUEST['version'];
$pkgs = explode(',', $_REQUEST['pkgs']);
sort($pkgs);
echo "ref=$ref version=$version pkgs=" . print_r($pkgs, 1);
// exec("docker images --format '{{.Repository}}:{{.Tag}} {{.ID}}'", $output);
//print_r($output);
// $refLen = strlen($ref);
// foreach ($output as $line) {
//   list($imageName, $imageId) = explode(' ', $line);
//   list($Ref, $Tag) = explode(':', $imageName);
//   if (strcmp($Ref, $ref) == 0) {
//     if (strcmp($Tag, 'latest') == 0) {
//       $latestId = $imageId;
//     } else {
//       if (strcmp($Tag, $version) == 0) {
//         $versionId = $imageId;
//       }
//     }
//   }
// }

echo "latestId=$latestId versionId=$versionId\n";

if ($latestId != $versionId) {
  $mes = "Version $version is not latest version; Update repository $ref:$version";
  echo $mes;
  exit(1);
}

echo "VERSIONID=$versionId\n";

$newImageName = "${ref}/" . implode('_', $pkgs) . ":" . $version;
echo "newImageName=$newImageName\n";
PKGS = implode(' ', $pkgs);
$Dockerfile = "FROM $imageName

RUN \
  apt-get install -y $PKGS

CMD /bin/bash
";

echo "Dockerfile=$Dockerfile\n";
$output = [];
$cmd = "echo \"$Dockerfile\" | docker build --no-cache -t $newImageName -";
echo "CMD=$cmd\n";
exec($cmd, $output);
print_r($output);

