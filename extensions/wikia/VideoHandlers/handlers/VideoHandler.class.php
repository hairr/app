<?php

/*
 * Handler layer between specyfic video handler and the rest of BitmapHandlers
 * Used mainly for identyfication of Video hanlders
 *
 * In future common handler logic will be migrated here
 * If you are using public video handler specyfic function write them down here
 *
 */

abstract class VideoHandler extends BitmapHandler {

	protected $api = null;
	protected $apiName = 'video/*';
	protected $videoId = '';
	protected $title = '';
	protected $metadata = null;
	protected $maxHeight = false;
	protected $thumbnailImage = null;
	protected static $aspectRatio = 1.7777778;
	protected static $classnameSuffix = 'VideoHandler';
	protected static $providerDetailUrlTemplate = '';	// must have a token called "$1"

	/**
	 * @param $image File
	 * @return array|bool
	 */
	function formatMetadata( $image ) {
		$meta = $image->getMetadata();

		if ( !$meta ) {
			return false;
		}
		$meta = unserialize( $meta );

		return $this->formatMetadataHelper( $meta );
	}

	function normaliseParams( $image, &$params ) {
		global $wgMaxImageArea, $wgMaxThumbnailArea;
		wfProfileIn( __METHOD__ );
		if ( !ImageHandler::normaliseParams( $image, $params ) ) {
			wfProfileOut( __METHOD__ );
			return false;
		}

		$params['physicalWidth'] = $params['width'];
		$params['physicalHeight'] = $params['height'];


		// Video files can be bigger than usuall images. We are alowing them to stretch up to WikiaFileHelper::maxWideoWidth px.
		if ( $params['physicalWidth'] > WikiaFileHelper::maxWideoWidth ) {
			$srcWidth = $image->getWidth( $params['page'] );
			$srcHeight = $image->getHeight( $params['page'] );
			$params['physicalWidth'] = WikiaFileHelper::maxWideoWidth;
			$params['physicalHeight'] = round( ($params['physicalWidth'] * $srcHeight ) / $srcWidth );
		}

		# Same as srcWidth * srcHeight above but:
		# - no free pass for jpeg
		# - thumbs should be smaller
		if ( $params['physicalWidth'] * $params['physicalHeight'] > $wgMaxThumbnailArea ) {
			wfProfileOut( __METHOD__ );
			return false;
		}
		wfProfileOut( __METHOD__ );
		return true;
	}

	function getPlayerAssetUrl() {
		return '';
	}

	/**
	 * Returns embed code for a provider
	 * @return string Embed HTML
	 */
	abstract function getEmbed( $articleId, $width, $autoplay = false, $isAjax = false, $postOnload = false );

	public function setMaxHeight( $height ) {
		$this->maxHeight = $height;
	}

	public function getProviderDetailUrl() {
		return str_replace('$1', $this->videoId, static::$providerDetailUrlTemplate);
	}

	public function getProviderHomeUrl() {
		return static::$providerHomeUrl;
	}


	function setVideoId( $videoId ){
		$this->videoId = $videoId;
	}

	public function getVideoId() {
		return $this->videoId;
	}

	function setTitle( $title ) {
		$this->title = $title;
	}

	function getTitle() {
		return $this->title;
	}

	function getAspectRatio(){
		global $wgCityId;
		wfProfileIn( __METHOD__ );
		$metadata = $this->getMetadata(true);
		$ratio = static::$aspectRatio;
		if (!empty($metadata['aspectRatio'])) {
			if (floatval($metadata['aspectRatio']) == 0) {
				error_log("VideoHandler aspectRatio warning: ". $wgCityId . ", ". $this->title);
			} else {
				$ratio = $metadata['aspectRatio'];
			}

		}
		wfProfileOut( __METHOD__ );
		return $ratio;
	}

	function getHeight( &$width ){

		$finalHeight =  (
			( $width / $this->getAspectRatio() ) +
			( 2 * $this->addExtraBorder( $width ) )
		);

		if ( (int) $this->maxHeight > 0 && (int) $finalHeight > $this->maxHeight ) {
			$finalHeight = $this->maxHeight;
			$width = $this->adjustWidth( $finalHeight );
		}

		return (integer) $finalHeight;
	}

	function adjustWidth( $height ) {

		$width = $height * $this->getAspectRatio();
		return (int) $width;
	}


	/**
	 * Get metadata. Connects with Api if metadata is not in database.
	 * @return mixed array of data, or serialized version
	 */
	function getMetadata( $unserialize = false ) {
		wfProfileIn( __METHOD__ );
		if ( empty($this->metadata)) {
			$this->metadata = $this->getApi() instanceof ApiWrapper
				? serialize( $this->getApi()->getMetadata() )
				: null;
		}
		wfProfileOut( __METHOD__ );
		return empty($unserialize) ? $this->metadata : unserialize($this->metadata);
	}

	/**
	 *
	 * @param string $metadata serialized array
	 */
	function setMetadata( $metadata ) {
		$this->metadata = $metadata;
	}

	/**
	 *
	 * @param ThumbnailImage $thumbnailImage
	 */
	function setThumbnailImage( $thumbnailImage ) {
		$this->thumbnailImage = $thumbnailImage;
	}

	/**
	 * Returns propper api for a current handler
	 * @return ApiWrapper object
	 */
	function getApi() {
		wfProfileIn( __METHOD__ );
		if ( !empty( $this->videoId ) && empty( $this->api ) ){
			$this->api = F::build ( $this->apiName, array( $this->videoId ) );
		}
		wfProfileOut( __METHOD__ );
		return $this->api;
	}

	function isMetadataValid( $image, $metadata ) {
		return true;
	}

	public function isBroken() {
		return strlen( (string) $this->videoId ) <= 3 ? true : false;
	}

	/**
	 *
	 * @return boolean
	 */
	protected function isHd() {
		$metadata = $this->getMetadata(true);
		return (!empty($metadata['hd']));
	}

	/**
	 *
	 * @return boolean
	 */
	protected function isAgeGate() {
		$metadata = $this->getMetadata(true);
		return (!empty($metadata['ageGate']));
	}

	/**
	 *
	 * @return int duration in seconds, or null
	 */
	protected function getDuration() {
		$metadata = $this->getMetadata(true);
		return (!empty($metadata['duration']) ? $metadata['duration'] : null);
	}

	public function getFormattedDuration() {

		$metadata = $this->getMetadata(true);
		if (!empty($metadata['duration'])) {

			$sec = $metadata['duration'];

			if ( (int)$sec == $sec ) {

				$hms = "";
				$hours = intval(intval($sec) / 3600);
				if ($hours > 0) {
					$hms .= str_pad($hours, 2, "0", STR_PAD_LEFT). ":";
				}

				$minutes = intval(($sec / 60) % 60);
				$hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ":";

				$seconds = intval($sec % 60);
				$hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);

				return $hms;

			} else {

				return $metadata['duration'];
			}
		}

		return '';
	}
	
	/** 
	 * Get the duration in ISO 8601 format for meta tag
	 * @return string
	 */
	public function getISO8601Duration() {
		$formattedDruation = $this->getFormattedDuration();
		if (!empty($formattedDruation)) {
			$segments = explode(':', $formattedDruation);
			$ret = "PT";
			if(count($segments) == 3) {
				$ret .= array_shift($segments) . 'H';
			} 
			$ret .= array_shift($segments) . 'M';
			$ret .= array_shift($segments) . 'S';
			
			return $ret;
		}		
		return '';		
	}

	/**
	 * Get the video id that is used for embed code
	 * @return string
	 */
	protected function getEmbedVideoId() {
		$metadata = $this->getMetadata(true);
		if (!empty($metadata['altVideoId'])) {
			return $metadata['altVideoId'];
		}
		return $this->videoId;
	}


	/**
	 * Returns fedault thumbnail mime type
	 * @return array thumbnail extension and MIME type
	 */
	function getThumbType( $ext, $mime, $params = null ) {
		return array( 'jpg', 'image/jpeg' );
	}

	/**
	 * Get the thumbnail code for videos
	 * @return object ThumbnailVideo object or error object
	 */
	function doTransform( $image, $dstPath, $dstUrl, $params, $flags = 0 ) {
		global $wgOut, $wgExtensionsPath;

		$oThumbnailImage = parent::doTransform( $image, $dstPath, $dstUrl, $params, $flags );

		return new ThumbnailVideo(
			$oThumbnailImage->file,
			$oThumbnailImage->url,
			$oThumbnailImage->width,
			$oThumbnailImage->height,
//			!empty( $this::$aspectRatio )
//				? round( $oThumbnailImage->width / $this::$aspectRatio )
//				: $oThumbnailImage->height,
			$oThumbnailImage->path,
			$oThumbnailImage->page
		);
	}

	public function addExtraBorder( $width ){
		return 0;
	}
}
