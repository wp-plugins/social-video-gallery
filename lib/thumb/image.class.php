<?php

###############################################################
# Thumbnail Image Class for Thumbnail Generator
###############################################################
# For updates visit http://www.zubrag.com/scripts/
############################################################### 

class Zubrag_image {

	var $save_to_file = true;
	var $image_type = -1;
	var $quality = 100;
	var $max_x = 100;
	var $max_y = 100;
	var $cut_x = 0;
	var $cut_y = 0;
	var $images_folder;
	var $from_name;
	var $extensions = array(
		1 => '.gif',
		2 => '.jpeg',
		3 => '.png'
	);
	var $content_types = array(
		1 => 'Content-type: image/gif',
		2 => 'Content-type: image/jpeg',
		3 => 'Content-type: image/png'
		
	);

	function SaveImage($im, $filename, $format = '') {

		$res = null;

		// ImageGIF is not included into some GD2 releases, so it might not work
		// output png if gifs are not supported
		if (($this->image_type == 1) && !function_exists('imagegif'))
			$this->image_type = 3;

		switch ($this->image_type) {
			case 1:
				if ($this->save_to_file) {
					$res = ImageGIF($im, $filename);
				} else {
					header("Content-type: image/gif");
					$res = ImageGIF($im);
				}
				break;
			case 2:
				if ($this->save_to_file) {
					$res = ImageJPEG($im, $filename, $this->quality);
				} else {
					header("Content-type: image/jpeg");
					$res = ImageJPEG($im, NULL, $this->quality);
				}
				break;
			case 3:
				if (PHP_VERSION >= '5.1.2') {
					// Convert to PNG quality.
					// PNG quality: 0 (best quality, bigger file) to 9 (worst quality, smaller file)
					$quality = 9 - min(round($this->quality / 10), 9);
					if ($this->save_to_file) {
						$res = ImagePNG($im, $filename, $quality);
					} else {
						header("Content-type: image/png");
						$res = ImagePNG($im, NULL, $quality);
					}
				} else {
					if ($this->save_to_file) {
						$res = ImagePNG($im, $filename);
					} else {
						header("Content-type: image/png");
						$res = ImagePNG($im);
					}
				}
				break;
		}
		if ($res) {
			$this->saveToFile($res);
		}
		return $res;
	}

	function ImageCreateFromType($type, $filename) {
		$im = null;
		switch ($type) {
			case 1:
				$im = ImageCreateFromGif($filename);
				break;
			case 2:
				$im = ImageCreateFromJpeg($filename);
				break;
			case 3:
				$im = ImageCreateFromPNG($filename);
				break;
		}
		return $im;
	}

	// generate thumb from image and save it
	function GenerateThumbFile($images_folder, $from_name) {

		$this->images_folder = $images_folder;
		$this->from_name = $from_name;

		// if src is URL then download file first
		$temp = false;
		
		$exists_file = $this->_fileExistsForAllFormats();
		if($exists_file)
		{
			header($this->content_types[$this->image_type]);
			readfile($exists_file);
			return;
		}		
		
		if (substr($from_name, 0, 7) == 'http://') {
			$tmpfname = tempnam("/tmp", "TmP-");
			$temp = @fopen($tmpfname, "w");
			$content = @file_get_contents($from_name);
			if ($temp) {
				@fwrite($temp, $content) or die("Cannot download image");
				@fclose($temp);
				$from_name = $tmpfname;
			} else {
				die("Cannot create temp file");
			}
		}

		// check if file exists
		if (!file_exists($from_name))
			die("Source image does not exist!");

		// get source image size (width/height/type)
		// orig_img_type 1 = GIF, 2 = JPG, 3 = PNG
		list($orig_x, $orig_y, $orig_img_type, $img_sizes) = @GetImageSize($from_name);
		$this->image_type = $orig_img_type;
		
		$this->saveToFile($content);

		// cut image if specified by user
		if ($this->cut_x > 0)
			$orig_x = min($this->cut_x, $orig_x);
		if ($this->cut_y > 0)
			$orig_y = min($this->cut_y, $orig_y);

		// check for allowed image types
		if ($orig_img_type < 1 or $orig_img_type > 3)
			die("Image type not supported");

		if ($orig_x > $this->max_x or $orig_y > $this->max_y) {
			// resize
			$per_x = $orig_x / $this->max_x;
			$per_y = $orig_y / $this->max_y;
			if ($per_y > $per_x) {
				$this->max_x = $orig_x / $per_y;
			} else {
				$this->max_y = $orig_y / $per_x;
			}
		} else {
			header($this->content_types[$this->image_type]);
			readfile($this->from_name);
			return;
		}

		if ($this->image_type == 1) {
			// should use this function for gifs (gifs are palette images)
			$ni = imagecreate($this->max_x, $this->max_y);
		} else {
			// Create a new true color image
			$ni = ImageCreateTrueColor($this->max_x, $this->max_y);
		}

		// Fill image with white background (255,255,255)
		$white = imagecolorallocate($ni, 255, 255, 255);
		imagefilledrectangle($ni, 0, 0, $this->max_x, $this->max_y, $white);
		// Create a new image from source file
		$im = $this->ImageCreateFromType($orig_img_type, $from_name);
		// Copy the palette from one image to another
		imagepalettecopy($ni, $im);
		// Copy and resize part of an image with resampling
		imagecopyresampled(
				$ni, $im, // destination, source
				0, 0, 0, 0, // dstX, dstY, srcX, srcY
				$this->max_x, $this->max_y, // dstW, dstH
				$orig_x, $orig_y);	// srcW, srcH
		// save thumb file
		$this->SaveImage($ni);

		if ($temp) {
			unlink($tmpfname); // this removes the file
		}
	}

	function saveToFile($source) {
		$filename = $this->_generateFileName();

		if (file_exists($filename))
			return false;

		@file_put_contents($filename, $source);
	}

	function _generateFileName() {
		return $this->images_folder . md5($this->from_name.$this->max_y.$this->max_y) . $this->extensions[$this->image_type];
	}
	
	function _fileExistsForAllFormats()
	{
		$filename = $this->images_folder . md5($this->from_name.$this->max_y.$this->max_y);
		$i = 1;
		foreach(array_values($this->extensions) AS $ext)
		{
			if(file_exists($filename.$ext))
			{
				$filename .= $ext;
				$this->image_type = $i;
				return $filename;
			}
			
			$i++;
				
		}
		
		return null;
		
	}

}

?>