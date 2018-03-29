<?php 

/*
* MP4 re-encoder
* Since we don't know where the mp4's come from we'll have to handle them ourselves
* While desktop browsers are more forgiving older phones might not be
*
*/ 

if(php_sapi_name() !== 'cli') exit('This script can only be called via CLI');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..');
include_once(ROOT.DS.'inc/config.inc.php');
include_once(ROOT.DS.'inc/core.php');

$pm = new PictshareModel();

$dir = ROOT.DS.'upload'.DS;
$dh  = opendir($dir);
$localfiles = array();

if(in_array('noskip',$argv))
{
    echo "Won't skip existing files\n\n";
    $allowskipping = false;
}
else
    $allowskipping = true;

//making sure ffmpeg is executable
system("chmod +x ".ROOT.DS.'bin'.DS.'ffmpeg');

echo "[i] Finding local mp4 files ..";
while (false !== ($filename = readdir($dh))) {
    $img = $dir.$filename.DS.$filename;
    if(!file_exists($img)) continue;
    $type = pathinfo($img, PATHINFO_EXTENSION);
    $type = $pm->isTypeAllowed($type);
    if($type=='mp4')
        $localfiles[] = $filename;
}

if(count($localfiles)==0) exit('No MP4 files found'."\n");

echo " done. Got ".count($localfiles)." files\n";

echo "[i] Starting to convert\n";
foreach($localfiles as $hash)
{
    $img = $dir.$hash.DS.$hash;
    $tmp = ROOT.DS.'tmp'.DS.$hash;
    if(file_exists($tmp) && $allowskipping==true)
        echo "Skipping $hash\n";
    else 
    {
        $cmd = "../bin/ffmpeg -y -i $img -loglevel panic -vcodec libx264 -an -profile:v baseline -level 3.0 -pix_fmt yuv420p -vf \"scale=trunc(iw/2)*2:trunc(ih/2)*2\" $tmp && cp $tmp $img";
        echo "  [i] Converting $hash";
        system($cmd);
        echo "\tdone\n";
    }

    if(in_array('ogg',$argv))
    {
        $tmp = ROOT.DS.'tmp'.DS.$hash.'.ogg';
        $ogg = $dir.$hash.DS.'ogg_1.'.$hash;
        if(file_exists($ogg) && $allowskipping==true)
            echo "Skipping OGG of $hash\n";
        else
        {
            echo "  [OGG] User wants OGG. Will do.. ";
            $cmd = "../bin/ffmpeg -y -i $img -loglevel panic -vcodec libtheora -an $tmp && cp $tmp $ogg";
            system($cmd);
            echo "done\n";
        }
    }

    if(in_array('webm',$argv))
    {
        $tmp = ROOT.DS.'tmp'.DS.$hash.'.webm';
        $webm = $dir.$hash.DS.'webm_1.'.$hash;
        if(file_exists($webm) && $allowskipping==true)
            echo "Skipping WEBM of $hash\n";
        else
        {
            echo "  [WEBM] User wants WEBM. Will do.. ";
            $cmd = "../bin/ffmpeg -y -i $img -loglevel panic -c:v libvpx -crf 10 -b:v 1M $tmp && cp $tmp $webm";
            system($cmd);
            echo "done\n";
        }
    }

    
}


function renderSize($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 

    // Uncomment one of the following alternatives
    $bytes /= pow(1024, $pow);
    // $bytes /= (1 << (10 * $pow)); 

    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 