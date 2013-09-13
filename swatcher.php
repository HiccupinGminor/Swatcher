<?php namespace Swatcher;

class SwatcherImage{

	/**
	 * Path to raw image
	 * @var string
	 */
	private $rawImg;

	/**
	 * The width of the image
	 * @var int
	 */
	private $width;

	/**
	 * The height of the image
	 * @var int
	 */
	private $height;

	/**
	 * The level of accuracy of the sampling desired
	 * @var int
	 */
	private $accuracy;

	/**
	 * Contains information on the image
	 * @var array
	 */
	private $imgInfo;

	/**
	 * Set the raw image, the accuracy of swatch sampling desired, image info
	 * @param string  $rawImg      path to an image file
	 * @param string  $accuracy    controls the fidelity of the swatch sampling. 
	 */
	public function __construct($rawImg, $accuracy = 'Low'){
		$this->rawImg = $rawImg;
		$this->accuracy = $accuracy;
		$this->imgInfo = getImageSize($rawImg);
		$this->width = $this->imgInfo[0];
		$this->height = $this->imgInfo[1];
		switch ($accuracy){
			case 'High':
				$this->accuracy = 1;
			break;
			case 'Medium':
				$this->accuracy = 2;
			break;
			case 'Low':
				$this->accuracy = 4;
			break;
		}
		
	}

	/**
	 * Utility function to save a PHP usable image as a property
	 * @return void
	 */
	private function saveImage(){
		switch($this->imgInfo['mime']){
			case 'image/jpeg':
				return ImageCreateFromJPEG($this->rawImg);
			break;
			case 'image/png':
				return imagecreatefrompng($this->rawImg);
			break;	
		}
	}

	/**
	 * Returns an array of top colors in a given image section. Defaults to analyzing the whole image
	 * @param  array $nw $count The coordinates of the northwest point of a given image or section
	 * @param  int $size The size of a section
	 * @return array A sorted array of colors in rgb with their associated frequency
	 */
	public function analyzePixels($nw = array('x' => 0, 'y' => 0), $size = null){
		$img = $this->saveImage($this->rawImg);
		
		//If an arbitrary section is specified for analysis (as in the case of a composite)
		if($size){
			$width = $height = $size;

		}
		else{
			$width = $this->width;
			$height = $this->height;
		}

		//X-axis markers
		$x = $nw['x'];
		$xEnd = $x + $width;

		$swatchArray = array();
		
		while($x < $xEnd){
			//Y-axis markers
			$y = $nw['y'];
			$yEnd = $y + $height;

			//Determine the dominant color[s] of the section
			while($y < $yEnd){
				$pixel = ImageColorAt($img, $x, $y);
				$rgb = imagecolorsforindex($img, $pixel);
				//Generate rgb
				//Add to swatch array (an unindexed group of color values) 
				array_push($swatchArray,  $this->rgbToCss($rgb));
				//}
				//Increment the y coordinate
				$y = $y + $this->accuracy;
			}
			$x = $x + $this->accuracy; 
		}
		//Tally each of the colors to determine the top colors for the section
		$swatchTally = array_count_values($swatchArray);

		//Sort the array, so the most dominant colors are at the top
		arsort($swatchTally);
		return $swatchTally;
	}

	/**
	 * Determine the dominant colors in a given section
	 * @param  int $count The number of colors desired
	 * @return array      Array of swatches
	 */
	public function topSwatches($count){

		$swatchTally = $this->analyzePixels($count);

		//Identify the keys (i.e. the color)
		$keys = array_keys($swatchTally);

		if($count == 1){
			return $keys[0];
		}
		else{
			$swatches = array();
			//Populate the result array with the top swatch colors
			for($i = 0; $i < $count; $i++){
				array_push($swatches, $keys[$i]);
			}
			return $swatches;
		}
	}
	
	/**
	 * Section image, and generate a representative color for each section of the image
	 * @param  $size width and height of each section
	 * @return array
	 */
	public function generateComposite($size){
		//Break down image into sections
		$xSections = floor($this->width / $size);
		$ySections = floor($this->height / $size);
		//Create an array of sections
		$sections = array();

		$x = $xCoord = 0;
		
		while($x < $xSections){
			
			$y = $yCoord = 0;
			$xCoord = $x * $size;
			
			while($y < $ySections){
				$yCoord = $y * $size ;

				//For each section, determine the average color
				$swatchColor = $this->analyzePixels(1, array('x'=>$xCoord, 'y'=>$yCoord), $size);

				//Save section information to greater array
				array_push($sections, array('nw' => array( 'x'=> $xCoord , 'y' => $yCoord), 'se' => array( 'x' => ($xCoord + $size), 'y' => ($yCoord + $size)), 'color' => $swatchColor));
				
				$yCoord += $size; 	
				$y++; 
			}
			$xCoord += $size;
			$x++;
		}
		return $sections;
	}

	/**
	 * Utility function to convert from an rgb array into a CSS-readable string
	 * @param  array $rgb representing a color in rgb notation [red, green, blue]
	 * @return string
	 */
	private function rgbToCss($rgb){
		$r = $rgb[0];
		$g = $rgb[1];
		$b = $rgb[2];
		return 'rgb('.$r.', '.$g.', '.$b.')';
	}
}
