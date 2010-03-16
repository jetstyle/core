<?php

class FileUpload {
    protected $allow = null; // белый список расширений
    //	protected $deny = null; // чёрный список расширений
    //	public $GRAPHICS = array('jpg', 'jpeg', 'gif', 'png', 'bmp');

    protected $baseDir = null;

    protected $dir = '';

    protected $isImage = false;

    protected $chmod = 0744; //какие права выставлять на загруженный файл

    protected $swappedDirs = array(); //для DirSwap(),  DirUnSwap();

    public function __construct() {
        if (null === $this->allow) {
            $this->allow = array();
            if (Config::get('upload_ext')) {
                $exts = explode(",", Config::get('upload_ext'));
                if (!empty($exts)) {
                    foreach ($exts as $ext) {
                        $ext = strtolower(trim($ext));
                        $this->allow[$ext] = $ext;
                    }
                }
            }
        }

        if (null === $this->baseDir) {
            $this->baseDir = Config::get('files_dir');
        }
    }

    public function setDir($dir) {
        $this->dir = trim($dir, '/');
    }

    public function setBaseDir($dir) {
        $this->baseDir = $dir;
    }

    public function setAllowedExts($exts) {
        if (is_array($exts)) {
            $this->allow = $exts;
        }
    }

    public function getDir() {
        return $this->dir;
    }

    public function getBaseDir() {
        return $this->baseDir;
    }

    public function getAllowedExts() {
        return $this->allow;
    }

    public function uploadFile($fileData, $params = NULL ) {
        if(!is_array($fileData)) {
            // treat as key of $_FILES array
            if (isset($_FILES[$fileData])) {
                $fileData = $_FILES[$fileData];
            }
            // treat as full path to file
            else {
                $fileData = array(
                    'tmp_name' => $fileData
                );
                $fileData['name'] = pathinfo($fileData['tmp_name'], PATHINFO_BASENAME);
            }
        }

        $uploadedFile = $fileData['tmp_name'];

        if (!is_uploaded_file($uploadedFile) && !( file_exists($uploadedFile) && is_file($uploadedFile) ) ) {
            throw new UploadException("No file to upload");
        }

        $ext = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));

        if( !$this->isAllowed($ext) ) {
            throw new UploadException("\"".$ext."\" extension is not allowed");
        }

        $this->isImage = File::isImageExt($ext);

        if ($params['filename']) {
            $fileName = $params['filename'];
        }
        else {
            $fileName = pathinfo($fileData['name'], PATHINFO_FILENAME);
        }


        Finder::useClass('Translit');
        $translit = new Translit();
        $fileName = $translit->supertag($fileName);

        // create directory, if needed
        $this->createDir();

        // construct full filename
        $dirname = $this->baseDir.$this->dir.($this->dir ? '/' : '');
        $add = '';
        while (file_exists($dirname.$fileName.$add.'.'.$ext)) {
            $add = '_'.mt_rand();
        }
        $fileName = $fileName.$add;
        $fileNameExt = $fileName.'.'.$ext;
        $fileNameFull = $dirname.$fileNameExt;


        $image = null;

        if ( is_array($params['actions']) && !empty($params['actions']) ) {
            if($this->isImage) {
                $image = $this->createResourceFromImage($uploadedFile);
            }

            foreach ($params['actions'] AS $key => $value) {
                switch ($key) {
                    case 'crop':
                        $this->createThumb($image, array('x' => $value[0], 'y' => $value[1]), true);
                        $this->cropImage($image, array('x' => $value[0], 'y' => $value[1]), $value[2]);
                        break;

                    case 'resize':
                        $this->createThumb($image, array('x' => $value[0], 'y' => $value[1]), false);
                        break;

                    case 'cropWithoutResize':
                        $this->cropImage($image, array('x' => $value[0], 'y' => $value[1]), $value[2]);
                        break;

                    case 'mask':
                        $this->applyMaskToImage($image, $value);
                        break;

                    case 'opacity':
                        $this->makeImageOpacity($image, $value);
                        break;

                //case 'make_flv':
                //$this->convertToFlv($file_name_full, $this->dir.$file_name.".flv");
                //@unlink($file_name_full);
                //break;
                }
            }
        }

        if ($image !== null) {
            $this->saveImageFromResource($image, $ext, $fileNameFull);
        }
        else {
            copy($uploadedFile, $fileNameFull);
        }

        // this should never happen
        if (!file_exists($fileNameFull)) {
            throw new UploadException("Upload failed due to unexpected error");
        }

        @chmod($fileNameFull, $this->chmod);

        $result = array(
            'name_full' => $fileNameFull,
            'name_short' => $fileNameExt,
            'filename' => $fileName,
            'ext' => $ext,
        );
        return $result;
    }

    protected function isAllowed($ext) {
        if( (!empty($this->allow) && !in_array($ext, $this->allow)) ) {
            return false;
        }
        return true;
    }

    protected function parseSizeParam($val) {
        $pattern = '/(<|>|>=|<=|=|==|)(\d+)/';
        preg_match($pattern, $val, $matches);
        return array($matches[1], $matches[2]);
    }

    protected function convertToFlv($fn, $ft) {
        exec("ffmpeg -i " . $fn . " -ar 22050 -ab 32 -f flv -s 320x240 ".$ft);
    }

    protected function createDir() {
        $dirname = $this->baseDir.$this->dir;

        if (!is_dir($dirname)) {
            if (!@mkdir($dirname, 0775, true)) {
                throw new UploadException("Can't create directory ".str_replace(Config::get('project_dir'), '', $dirname));
            }
        }
        elseif (!is_writable($dirname)) {
            throw new UploadException("Directory ".str_replace(Config::get('project_dir'), '', $dirname)." is not writable");
        }
    }

    protected function createResourceFromImage($filename) {
        $size = getimagesize($filename);

        $img = null;

        if ($size[2]==2) {
            $img = imagecreatefromjpeg ($filename);
        }
        elseif ($size[2]==1) {
            $img = imagecreatefromgif ($filename);
        }
        elseif ($size[2]==3) {
            $img = imagecreatefrompng ($filename);
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }

        return $img;
    }

    protected function saveImageFromResource($resource, $type = 'jpeg', $filename) {
        switch ($type) {
            case 'png':
                imagepng($resource, $filename, 2);
                break;

            case 'gif':
                imagegif($resource, $filename);
                break;

            case 'jpeg':
            case 'jpg':
            default:
                imagejpeg ($resource, $filename, 96);
                break;
        }
    }


    // ###################################### ReSize Image ################################# //
    public function createThumb(&$img, $thumbSize, $byLowerSide = false) {
        $size = array(
            imagesx($img),
            imagesy($img),
        );


        if (($size[0] <= $thumbSize['x']) && ($size[1] <= $thumbSize['y'])) {
            return;
        }


        $xratio = $size[0] / $thumbSize['x'];
        $yratio = $size[1] / $thumbSize['y'];

        if ($xratio > $yratio) {
            $ratio = $byLowerSide ? $yratio : $xratio;
        }
        else {
            $ratio = $byLowerSide ? $xratio : $yratio;
        }
        $newWidth = round($size[0] / $ratio);
        $newHeight = round($size[1] / $ratio);

        $thumbnail = imagecreatetruecolor ($newWidth, $newHeight);

        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $tColor = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $tColor);

        imagecopyresampled ($thumbnail, $img, 0,0,0,0, $newWidth, $newHeight, $size[0], $size[1]);

        imagedestroy($img);
        $img = $thumbnail;
    }

    protected function cropImage(&$img, $size, $cropType) {
        $w = imagesx($img);
        $h = imagesy($img);

        $thumbnail = imagecreatetruecolor ($size['x'], $size['y']);

        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $tColor = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $size['x'], $size['y'], $tColor);

        if ($cropType === 'center') {
            imagecopy($thumbnail, $img, 0, 0, round(($w - $size['x']) / 2), round(($h - $size['y']) / 2), $size['x'], $size['y']);
        }
        elseif ($cropType === 'bottom') {
            imagecopy($thumbnail, $img, 0, 0, ($w - $size['x']), ($h - $size['y']), $size['x'], $size['y']);
        }
        else {
            imagecopy($thumbnail, $img, 0, 0, 0, 0, $size['x'], $size['y']);
        }
        imagedestroy($img);
        $img = $thumbnail;
    }

    protected function applyMaskToImage(&$img, $maskFilename) {
        $sourceMask = $this->createResourceFromImage($maskFilename);

        $sourceMaskSize = array(
            'x' => imagesx($sourceMask),
            'y' => imagesy($sourceMask)
        );

        $maskSize = array(
            'x' => imagesx($img),
            'y' => imagesy($img),
        );

        $mask = imagecreatetruecolor($maskSize['x'], $maskSize['y']);
        imagecopyresampled ($mask, $sourceMask, 0,0,0,0, $maskSize['x'], $maskSize['y'], $sourceMaskSize['x'], $sourceMaskSize['y']);
        imageDestroy($sourceMask);

        for ($x = 0; $x < $maskSize['x']; $x++) { // each row
            for ($y = 0; $y < $maskSize['y']; $y++) { // each pixel
                $maskRGBColor = imagecolorsforindex($mask, imagecolorat($mask, $x, $y));
                if ($maskRGBColor['red'] > 250 && $maskRGBColor['blue'] > 250 && $maskRGBColor['blue'] > 250) {
                    $rgbColor = imagecolorsforindex($img, imagecolorat($img, $x, $y));
                    $newColor = imagecolorallocatealpha ($img, $rgbColor['red'], $rgbColor['green'], $rgbColor['blue'], 126);
                    imagesetpixel ($img, $x, $y, $newColor);
                }
            }
        }
    }

    /**
     *	$img - image resource
     * 	$opacity - from 0 to 100
     */
    protected function makeImageOpacity(&$img, $opacity = 63) {
        $w = imagesx($img);
        $h = imagesy($img);

        $opacity = round($opacity / 100 * 127);

        for ($x = 0; $x < $w; $x++) { // each row
            for ($y = 0; $y < $h; $y++) { // each pixel
                $rgbColor = imagecolorsforindex($img, imagecolorat($img, $x, $y));
                if ($rgbColor['alpha'] < 100) {
                    $newColor = imagecolorallocatealpha ($img, $rgbColor['red'], $rgbColor['green'], $rgbColor['blue'], $opacity);
                    imagesetpixel ($img, $x, $y, $newColor);
                }
            }
        }
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////
    ////
    ////                  p h p U n s h a r p M a s k
    ////
    ////		Unsharp mask algorithm by Torstein Hшnsi 2003.
    ////		thoensi@netcom.no
    ////		Please leave this notice.
    ////
    ///////////////////////////////////////////////////////////////////////////////////////////////

    public function unsharpMask(&$img, $amount = 100, $radius = .5, $threshold = 3) {

    // $img is an image that is already created within php using
    // imgcreatetruecolor. No url! $img must be a truecolor image.

    // Attempt to calibrate the parameters to Photoshop:
        if ($amount > 500) {
            $amount = 500;
        }
        $amount = $amount * 0.016;
        if ($radius > 50) {
            $radius = 50;
        }
        $radius = $radius * 2;
        if ($threshold > 255) {
            $threshold = 255;
        }

        $radius = abs(round($radius)); 	// Only integers make sense.
        if ($radius == 0) {
            return true;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $imgCanvas = imagecreatetruecolor($w, $h);
        $imgCanvas2 = imagecreatetruecolor($w, $h);
        $imgBlur = imagecreatetruecolor($w, $h);
        $imgBlur2 = imagecreatetruecolor($w, $h);
        imagecopy ($imgCanvas, $img, 0, 0, 0, 0, $w, $h);
        imagecopy ($imgCanvas2, $img, 0, 0, 0, 0, $w, $h);


        // Gaussian blur matrix:
        //
        //	1	2	1
        //	2	4	2
        //	1	2	1
        //
        //////////////////////////////////////////////////

        // Move copies of the image around one pixel at the time and merge them with weight
        // according to the matrix. The same matrix is simply repeated for higher radii.
        for ($i = 0; $i < $radius; $i++) {
            imagecopy ($imgBlur, $imgCanvas, 0, 0, 1, 1, $w - 1, $h - 1); // up left
            imagecopymerge ($imgBlur, $imgCanvas, 1, 1, 0, 0, $w, $h, 50); // down right
            imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 1, 0, $w - 1, $h, 33.33333); // down left
            imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 1, $w, $h - 1, 25); // up right
            imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 1, 0, $w - 1, $h, 33.33333); // left
            imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 0, $w, $h, 25); // right
            imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 20 ); // up
            imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 16.666667); // down
            imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h, 50); // center
            imagecopy ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);

            // During the loop above the blurred copy darkens, possibly due to a roundoff
            // error. Therefore the sharp picture has to go through the same loop to
            // produce a similar image for comparison. This is not a good thing, as processing
            // time increases heavily.
            imagecopy ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h);
            imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 50);
            imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 33.33333);
            imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 25);
            imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 33.33333);
            imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 25);
            imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 20 );
            imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 16.666667);
            imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 50);
            imagecopy ($imgCanvas2, $imgBlur2, 0, 0, 0, 0, $w, $h);
        }
        imagedestroy($imgBlur);
        imagedestroy($imgBlur2);

        // Calculate the difference between the blurred pixels and the original
        // and set the pixels
        for ($x = 0; $x < $w; $x++) { // each row
            for ($y = 0; $y < $h; $y++) { // each pixel

                $rgbOrig = ImageColorAt($imgCanvas2, $x, $y);
                $rOrig = (($rgbOrig >> 16) & 0xFF);
                $gOrig = (($rgbOrig >> 8) & 0xFF);
                $bOrig = ($rgbOrig & 0xFF);

                $rgbBlur = ImageColorAt($imgCanvas, $x, $y);

                $rBlur = (($rgbBlur >> 16) & 0xFF);
                $gBlur = (($rgbBlur >> 8) & 0xFF);
                $bBlur = ($rgbBlur & 0xFF);

                // When the masked pixels differ less from the original
                // than the threshold specifies, they are set to their original value.
                $rNew = (abs($rOrig - $rBlur) >= $threshold) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))	: $rOrig;
                $gNew = (abs($gOrig - $gBlur) >= $threshold) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))	: $gOrig;
                $bNew = (abs($bOrig - $bBlur) >= $threshold) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))	: $bOrig;

                $pixCol = imagecolorallocate ($img, $rNew, $gNew, $bNew);
                imagesetpixel ($img, $x, $y, $pixCol);
            }
        }
        imagedestroy($imgCanvas);
        imagedestroy($imgCanvas2);

        return true;
    }
}
?>