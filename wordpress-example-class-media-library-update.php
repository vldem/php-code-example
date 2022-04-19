<?php

/**
 * Media Library update class
 */

class MediaLibraryUpdate
{

	// crop parameter name that is used by Crop Thumbnails plugin to save image metadata
	const CROP_PARAMETER_NAME = 'cpt_last_cropping_data';

	/**
	 * Update image in Media Library by Post ID
	 *
	 * @param int $postId        The Post ID of the record of updating imagein in post table
	 * @param int $keepCropping  1 - keep cropping selection for image, 0 - do not keep
	 *
	 */
	public function mediaLibraryUpdate($postId, $keepCropping = 0) {
		global $wpdb;
		save_log(LOG_WARNING, "[media_library_update] Media update started for $postId");
		// skip non-image attachments
		if (!wp_attachment_is_image($postId)) {
			return false;
		}

		$result = true;

		ignore_user_abort(true);
		//@codeCoverageIgnoreStart
		if ( defined('TEMPLATE_VERSION') ) {
			require_once (ABSPATH . 'wp-admin/includes/image.php');
		}
		//@codeCoverageIgnoreEnd

		// get attachment metadata
		$meta = wp_get_attachment_metadata( $postId, true );
		$cropMeta = $this->getCropMetadata( $meta );

		// if empty try to restore by post guid
		if ( empty($meta) ) {
			$sql = "SELECT guid FROM $wpdb->posts WHERE ID = $postId AND post_type = 'attachment'";
			$guid = $wpdb->get_var($sql);
			if ($guid) {
				save_log(LOG_WARNING, "[media_library_update] ($postId) wp_update_attachment_metadata try to use guid:", $guid);
				$meta['file'] = substr($guid, strlen(get_option('fileupload_url')));
			}
		}

		// get base dir for blog files
		$upload_dir = wp_upload_dir();
		$basedir = $upload_dir['basedir'];

		// update image and metadata
		$file = $basedir . '/' . ( isset( $meta['file'] ) ? $meta['file'] : '' );
		if ( isset( $meta['file'] ) and $meta['file'] <>'' ) {

			if ( $this->isFileExist( $file ) ) {
				save_log(LOG_WARNING, "[media_library_update] ($postId) $file");

				$this->deleteOldThumbFile( $meta, $basedir );
				$meta = wp_generate_attachment_metadata( $postId, $file );

				if ( !empty( $cropMeta ) && !empty( $meta ) && $keepCropping ) {
					$meta = $this->generateCroppedThumbnails( $postId, $file, $meta, $cropMeta );
				}

				if ( !empty( $meta ) ) {
					$r = $this->updateAttachmentMetadata( $postId, $meta );
					//$r = wp_update_attachment_metadata( $postId, $meta );
					save_log(LOG_WARNING, "[media_library_update] ($postId) wp_update_attachment_metadata result=".var_export($r,true) );
				} else {
					save_log(LOG_WARNING, "[media_library_update] ($postId) wp_update_attachment_metadata skip (empty meta generated)" );
					$result = false;
				}
			} else {
				save_log(LOG_WARNING, "[media_library_update] ($postId) wp_update_attachment_metadata skip (no $file)");
				$result = false;
			}

		} else {
			save_log(LOG_WARNING, "[media_library_update] ($postId) wp_update_attachment_metadata skip (no image file)");
			$result = false;
		}
		save_log(LOG_WARNING, "[media_library_update] Media update finished for $postId");

		return $result;
	}

	/**
	 * Generate cropped thumbnails for specified image's post and file
	 *
	 * @param string $postId    The attachment post id
	 * @param string $file      The image file name including full path
	 * @param array  $meta      The metadata of image
	 * @param array  $cropMeta  The crop metadata of thumbnails
	 * @return array             updated image metadata with crop parametersp
	 *
	 */
	public function generateCroppedThumbnails( $postId, $file, $meta, $cropMeta ) {
		foreach ( $cropMeta as $thumbnailName => $cropArray ) {
			$cropParams = $cropArray[ self::CROP_PARAMETER_NAME ];
			$width = ( isset( $meta['sizes'][$thumbnailName]['width'] ) ? $meta['sizes'][$thumbnailName]['width'] : false );
			$height = ( isset( $meta['sizes'][$thumbnailName]['height'] ) ? $meta['sizes'][$thumbnailName]['height'] : false );

			if ( !$width || !$height ) {
				if ( isset($GLOBALS['CROP_THUMBNAILS_HELPER']) ) {
					$dbImages = $GLOBALS['CROP_THUMBNAILS_HELPER']->getImageSizes();
					if ( isset( $dbImages[$thumbnailName] ) ) {
						$width = ( isset( $dbImages[$thumbnailName]['width'] ) ? $dbImages[$thumbnailName]['width'] : false );
						$height = ( isset( $dbImages[$thumbnailName]['height'] ) ? $dbImages[$thumbnailName]['height'] : false );
					}
				}
				if (!$width || !$height ) {
					//cannot define size. skip cropping
					save_log(LOG_WARNING, "[media_library_update] ($postId) skip cropping (cannot define image size)" );
					continue;
				}
			}

			//generate current file name of thumbnail
			$currentFilePath = $this->generateCurrentFilePath( $file, $width, $height );

			//genrate temporary file for cropping
			$temporaryCopyFile = $this->generateTemporaryFilePath( $currentFilePath );

			//crop image according to crop parameters
			$resultWpCropImage = $this->doWpCrop( $file, $cropParams, $width, $height, $temporaryCopyFile );

			// replace thumbnail file with cropped one
			if ( empty($resultWpCropImage) || is_wp_error($resultWpCropImage) ) {
				save_log(LOG_WARNING, "[media_library_update] ($postId) Can't generate filesize $thumbnailName skip" );
				continue;
			} else {
				if ( !$this->replaceThumbnailFileWithCropped( $postId, $thumbnailName, $resultWpCropImage, $currentFilePath ) ) {
					continue;
				}
			}

			// create new meta
			$newValues = [];
			if ( !empty( $meta['sizes'][$thumbnailName] ) ) {
				$newValues = $meta['sizes'][$thumbnailName];
			} else {
				$newValues['file'] = wp_basename($currentFilePath);
				$newValues['width'] = $width;
				$newValues['height'] = $height;
				$fileTypeInformations = wp_check_filetype($currentFilePath);
				$newValues['mime-type'] = $fileTypeInformations['type'];
			}
			$meta['sizes'][$thumbnailName] = array_merge( $newValues, $cropArray );

		}
		return $meta;
	}

	/**
	 * This is the place where crop-thumbnails crops the images - using the wordpress default function.
	 *
	 * @param object $file                The source file
	 * @param object $crop_params         The cropping parameters
	 * @param object $width               The destination width
	 * @param object $height              The destination height.
	 * @param object $temporaryCopyFile   The target file-path
	 * @return string|WP_Error|false      New filepath on success, WP_Error or false on failure.
	 *
	 */
	public function doWpCrop($file, $crop_params, $width, $height, $temporaryCopyFile) {
		return wp_crop_image(								// * @return string|WP_Error|false New filepath on success, WP_Error or false on failure.
			$file,											// * @param string|int $src The source file or Attachment ID.
			$crop_params['x'],								// * @param int $src_x The start x position to crop from.
			$crop_params['y'],								// * @param int $src_y The start y position to crop from.
			$crop_params['x2'] - $crop_params['x'],			// * @param int $src_w The width to crop.
			$crop_params['y2'] - $crop_params['y'],			// * @param int $src_h The height to crop.
			$width,											// * @param int $dst_w The destination width.
			$height,										// * @param int $dst_h The destination height.
			false,											// * @param int $src_abs Optional. If the source crop points are absolute.
			$temporaryCopyFile								// * @param string $dst_file Optional. The destination file to write to.
		);
	}


	/**
	 * Upate metadata of image post and related post (by WPML translation if exists) during Media Library update.
	 *
	 * @param int $postId  The Post ID of the record of updating imagein in post table
	 * @param array $meta   The metadata of updating image
	 * @return true|false   true if metadata has been updated, otherwias false.
	 *                      If no difference between current and new metadata then return false as well.
	 *
	 */
	public function updateAttachmentMetadata( $postId, $meta ) {
		global $sitepress, $wpml_post_translations;

		$result = wp_update_attachment_metadata( $postId, $meta ); // update meta for main post

		if ( isset( $sitepress ) ) {
			$postLang = $sitepress->get_language_for_element( $postId, 'post_post' );
			$translations = $wpml_post_translations->get_element_translations( $postId );
			foreach ( $translations as $lang=>$destinationImageId ) {
				if ( $lang <> $postLang ) {
					//update meta for related post by WPML translation
					$result = wp_update_attachment_metadata( $destinationImageId, $meta) && $result;
				}
			}
		}

		return $result;
	}

	/**
	 * Return crop metadata from image metadata if exists.
	 *
	 * @param array $meta  The array of image metadata
	 * @return array       with crop parameters for thumbnails that have 'cpt_last_cropping_data'
	 *                     parameter generated by Crop Thumbnails plugin.
	 *
	 */
	public function getCropMetadata( $meta ) {
		$cropMeta = array();

		if ( is_array($meta) && array_key_exists('sizes', $meta) ) {
			foreach ($meta['sizes'] as $name => $value) {
				if ( array_key_exists( self::CROP_PARAMETER_NAME, $value) ) {
					$cropMeta[$name] = [ self::CROP_PARAMETER_NAME => $value[self::CROP_PARAMETER_NAME] ];
				}
			}
		}

		return $cropMeta;
	}

	/**
	 * Generate the Filename (and path) of the thumbnail based on width and height
	 *
	 * @param string $file   The source file name
	 * @param int $width     The destination thumbnail width
	 * @param int $height    The destination thumbnail height
	 * @return string        path to the new image
	 *
	 */
	public function generateCurrentFilePath( $file, $width, $height ) {
		$info = pathinfo($file);
		$dir = $info['dirname'];
		$ext = $info['extension'];
		$name = wp_basename($file, '.'.$ext);
		$suffix = $width.'x'.$height;
		return $dir.'/'.$name.'-'.$suffix.'.'.$ext;
	}

	/**
	 * Generate the temporary Filename (and path) for image cropping
	 *
	 * @param string $file  The source file name
	 * @return string       new path to the temporary file
	 *
	 */
	public function generateTemporaryFilePath( $file ) {
		$filePathInfo = pathinfo($file);
		$filePathInfo['basename'] = wp_basename($file);
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $filePathInfo['basename'];
	}

	/**
	 * Replace current thumbnail file with cropped image file
	 *
	 * @param int $postId                  The attachment post id
	 * @param string $thumbnailName        The thumbnail name
	 * @param string $sourceFilePath       The source file name
	 * @param string $destinationFilePath  The destination file name
	 * @return true|false  true if sucess, false on failure
	 * @codeCoverageIgnore
	 */
	public function replaceThumbnailFileWithCropped( $postId, $thumbnailName, $sourceFilePath, $destinationFilePath ) {
		if ( !@copy( $sourceFilePath, $destinationFilePath ) ) {
			save_log(LOG_WARNING, "[media_library_update] ($postId) Can't copy temporary file to media librar for $thumbnailName" );
			return false;
		}
		if ( !@unlink( $sourceFilePath ) ) {
			save_log(LOG_WARNING, "[media_library_update] ($postId) Can't delete temporary file for $thumbnailName" );
			return false;
		}
		return true;
	}

	/**
	 * Delete old thumbnails file before create new ones.
	 *
	 * @param array $meta      The metadata of image
	 * @param string $basedur  The path where thumbnails files are located
	 * @return true|false      true if sucess, false on failure
	 *
	 */
	public function deleteOldThumbFile( $meta, $basedir) {
		$result = true;
		if ( isset($meta['sizes']) ) {
			$file = $basedir . '/' . $meta['file'];
			$info = pathinfo($file);
			$dir = $info['dirname'];

			foreach ( $meta['sizes'] as $thumbnailName => $size ) {
				$fileToDelete = $dir. '/' . $size['file'];
				if ( !@unlink( $fileToDelete ) ) {
					save_log(LOG_WARNING, "[media_library_update] Can't delete old file $fileToDelete for $thumbnailName" );
					$result = false;
				}
			}
		}
		return $result;

	}

	/**
	 * Check if file exists
	 *
	 * @param string $file  The file path to check
	 * @return true|false   true if the file exists, otherwise false
	 * @codeCoverageIgnore
	 *
	 */
	public function isFileExist($file) {
		return file_exists($file);
	}

}