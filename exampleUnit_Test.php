<?php

/**
 * This is unit test case for class MediaLibraryUpdate.
 */

use Brain\Monkey\Functions;

require_once __DIR__ . '/../../../../wordpress/wp-content/themes/mytheme/inc/class-media-library-update.php';


class TestMediaLibraryUpdateClass extends MyTestCase
{

	public $meta;
	public $metaNoCrop;
	public $metaNoSize;
	public $cropMeta;
	public $metaNoSizewithCroppingData;

	/**
	 * Prepare data for tests:
	 * @var meta        array of image metadata
	 * @var metaNoCrop  array of image metadata without cropping parameters
	 * @var cropMeta    array of cropping parameters
	 */
	private function initData() {
		$this->meta = array(
			'width' => 1640,
			'height' => 1048,
			'file' => '2021/12/mont-blanc-5-hiking-to-lac-b-1507730.jpg',
			'sizes' =>  array (
				'thumbnail1' =>  array (
					'file' => 'mont-blanc-5-hiking-to-lac-b-1507730-320x320.jpg',
					'width' => 320,
					'height' => 320,
					'mime-type' => 'image/jpeg',
					'cpt_last_cropping_data' =>  array ( 'x' => 3, 'y' => 10, 'x2' => 513, 'y2' => 520, 'original_width' => 1640, 'original_height' => 1048, ),
				),
				'thumbnail2' =>  array (
					'file' => 'mont-blanc-5-hiking-to-lac-b-1507730-1170x600.jpg',
					'width' => 1170,
					'height' => 600,
					'mime-type' => 'image/jpeg',
					'cpt_last_cropping_data' =>  array ( 'x' => 387, 'y' => 398, 'x2' => 1640, 'y2' => 1040, 'original_width' => 1640, 'original_height' => 1048, ),
				),
			),
			'image_meta' =>  array (
				'aperture' => '0',
				'credit' => '',
				'camera' => '',
				'caption' => '',
				'created_timestamp' => '0',
				'copyright' => '',
				'focal_length' => '0',
				'iso' => '0',
				'shutter_speed' => '0',
				'title' => '',
				'orientation' => '1',
				'keywords' =>  array ( ),
			),
		);

		$this->metaNoCrop = array(
			'width' => 1640,
			'height' => 1048,
			'file' => '2021/12/mont-blanc-5-hiking-to-lac-b-1507730.jpg',
			'sizes' =>  array (
				'thumbnail1' =>  array (
					'file' => 'mont-blanc-5-hiking-to-lac-b-1507730-320x320.jpg',
					'width' => 320,
					'height' => 320,
					'mime-type' => 'image/jpeg',
				),
				'thumbnail2' =>  array (
					'file' => 'mont-blanc-5-hiking-to-lac-b-1507730-1170x600.jpg',
					'width' => 1170,
					'height' => 600,
					'mime-type' => 'image/jpeg',
				),
			),
			'image_meta' =>  array (
				'aperture' => '0',
				'credit' => '',
				'camera' => '',
				'caption' => '',
				'created_timestamp' => '0',
				'copyright' => '',
				'focal_length' => '0',
				'iso' => '0',
				'shutter_speed' => '0',
				'title' => '',
				'orientation' => '1',
				'keywords' =>  array ( ),
			),
		);

		$this->metaNoSize = array(
			'width' => 1640,
			'height' => 1048,
			'file' => '2021/12/mont-blanc-5-hiking-to-lac-b-1507730.jpg',
			'sizes' =>  array (
				'thumbnail1' =>  array (
					'file' => 'mont-blanc-5-hiking-to-lac-b-1507730-320x320.jpg',
					'width' => 320,
					'height' => 320,
					'mime-type' => 'image/jpeg',
				),
			),
			'image_meta' =>  array (
				'aperture' => '0',
				'credit' => '',
				'camera' => '',
				'caption' => '',
				'created_timestamp' => '0',
				'copyright' => '',
				'focal_length' => '0',
				'iso' => '0',
				'shutter_speed' => '0',
				'title' => '',
				'orientation' => '1',
				'keywords' =>  array ( ),
			),
		);

		$this->metaNoSizewithCroppingData = array(
			'width' => 1640,
			'height' => 1048,
			'file' => '2021/12/mont-blanc-5-hiking-to-lac-b-1507730.jpg',
			'sizes' =>  array (
				'thumbnail1' =>  array (
					'file' => 'mont-blanc-5-hiking-to-lac-b-1507730-320x320.jpg',
					'width' => 320,
					'height' => 320,
					'mime-type' => 'image/jpeg',
					'cpt_last_cropping_data' =>  array ( 'x' => 3, 'y' => 10, 'x2' => 513, 'y2' => 520, 'original_width' => 1640, 'original_height' => 1048, ),
				),
			),
			'image_meta' =>  array (
				'aperture' => '0',
				'credit' => '',
				'camera' => '',
				'caption' => '',
				'created_timestamp' => '0',
				'copyright' => '',
				'focal_length' => '0',
				'iso' => '0',
				'shutter_speed' => '0',
				'title' => '',
				'orientation' => '1',
				'keywords' =>  array ( ),
			),
		);

		$this->cropMeta = array(
			'thumbnail1' =>  array (
				'cpt_last_cropping_data' =>  array ( 'x' => 3, 'y' => 10, 'x2' => 513, 'y2' => 520, 'original_width' => 1640, 'original_height' => 1048, ),
			),
			'thumbnail2' =>  array (
				'cpt_last_cropping_data' =>  array ( 'x' => 387, 'y' => 398, 'x2' => 1640, 'y2' => 1040, 'original_width' => 1640, 'original_height' => 1048, ),
			),
		);
	}


	/**
	 * This is a fixture that's called by phpunit automatically before each test method run
	 */
	protected function setUp(): void {
		global $wpdb;

		parent::setUp();

		//Initialize test data for tests (meta, metaNoCrop, cropMeta)
		$this->initData();

		//Declare common and default mockes and stubs
		Functions\stubs([
			'wp_basename' => function( $path, $suffix = '' ) {
				return basename($path, $suffix);
			},
			'wp_upload_dir' => function() {
				return array (
					'path'    => '/home/test/htdocs/wp-content/uploads/2022/03',
					'url'     => 'http://test.com/wp-content/uploads/2022/03',
					'subdir'  => '/2022/03',
					'basedir' => '/home/test/htdocs/wp-content/uploads',
					'baseurl' => 'http://test.com/wp-content/uploads',
					'error'   => false,
				);
			}
		]);

		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->last_error = '';
		$wpdb->last_sql = '';

	}

	/**
	 * This is a fixture that's called by phpunit automatically before test class is started to test.
	 */
	public static function setUpBeforeClass(): void {

		parent::setUpBeforeClass();

	}

	/**
	 * This is a fixture that's called by phpunit automatically after test class is tested
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
	}

	/**
	 * Common Data Provider
	 * @param $test  name of test method that calls data provider. This value is provided by phpunit automatically.
	 * @return CsvFilerIterator that reads rows from the file
	 */
	public function getDataProvider( $test ) {
		$file = __DIR__ . '/' . $test . '.csv';
		return new CsvFileIterator( __DIR__ . '/' . $test . '.csv');
	}

	/**
	 * @covers MediaLibraryUpdate::generateTemporaryFilePath
	 */
	public function testTemporaryFilePathCanBeGenerated () {
		$file = 'test-image.jpg';
		$this->assertEquals(
			'/home/test/htdocs/wp-content/uploads'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$file,
			( new MediaLibraryUpdate )->generateTemporaryFilePath( $file )
		);
	}

	/**
	 * @covers MediaLibraryUpdate::generateCurrentFilePath
	 */
	public function testCurrentFilePathCanBeGenerated () {
		$file = '/home/test/htdocs/wp-content/uploads/2022/03/test-image.jpg';
		$this->assertEquals(
			'/home/test/htdocs/wp-content/uploads/2022/03/test-image-320x200.jpg',
			( new MediaLibraryUpdate )->generateCurrentFilePath( $file , 320, 200 )
		);
	}

	/**
	 * @covers MediaLibraryUpdate::getCropMetadata
	 */
	public function testCropMetadataCanBeAccessed () {
		$this->assertEquals( $this->cropMeta, ( new MediaLibraryUpdate )->getCropMetadata( $this->meta ) );
		$this->assertEmpty( ( new MediaLibraryUpdate )->getCropMetadata( $this->metaNoCrop ) );
	}

	/**
	 * @covers MediaLibraryUpdate::updateAttachmentMetadata
	 */
	public function testAttachmentMetadataCanBeUpdated () {
		global $sitepress, $wpml_post_translations;

		Functions\expect("wp_update_attachment_metadata")->twice()->with( Mockery::type('int'), Mockery::type('array') )->andReturn(true);

		$sitepress = Mockery::mock( 'sitepress' );
		$sitepress->shouldReceive( 'get_language_for_element' )
			->once()
			->with(Mockery::type('int'), Mockery::type('string'))
			->andReturn('en');

		$wpml_post_translations = Mockery::mock( 'wpml_post_translations' );
		$wpml_post_translations->shouldReceive( 'get_element_translations' )
			->once()
			->with(Mockery::type('int'))
			->andReturn( array( 'de' => 330, 'en' => 331 ) );

		$this->assertTrue(
			( new MediaLibraryUpdate )->updateAttachmentMetadata( 330, $this->meta )
		);
	}

	/**
	 * @covers MediaLibraryUpdate::generateCroppedThumbnails
	 * @covers MediaLibraryUpdate::doWpCrop
	 * @dataProvider getDataProvider
	 */
	public function testCroppedThumbnailsCanBeGenerated ( $isWpError, $fileReplaceReturn, $isNoSize, $cropThumbnailsExists) {
		$file = 'mont-blanc-5-hiking-to-lac-b-1507730.jpg';

		//Create mocks for some methods of class MediaLibraryUpdate that we don't want to use to simplify the test.
		$class = \Mockery::mock('MediaLibraryUpdate')->makePartial();
		$class->shouldReceive('replaceThumbnailFileWithCropped')->andReturnUsing( function() use( $fileReplaceReturn ) {
			return $fileReplaceReturn;
		});

		//Create mocks for other Wordpress functions
		Functions\expect("is_wp_error")->with( Mockery::any() )->andReturnUsing( function() use( $isWpError ) {
			return $isWpError;
		});

		Functions\expect("wp_check_filetype")->with( Mockery::type('string') )->andReturn( array('type'=>'image/jpeg'));

		Functions\expect("wp_crop_image")
			->atMost()
			->times(2)
			->with(
				Mockery::type('string'),
				Mockery::type('int'),
				Mockery::type('int'),
				Mockery::type('int'),
				Mockery::type('int'),
				Mockery::type('int'),
				Mockery::type('int'),
				false,
				Mockery::type('string')
			)
			->andReturn('test-image-300x300.jpg');

		global $CROP_THUMBNAILS_HELPER;
		$CROP_THUMBNAILS_HELPER = Mockery::mock( 'CROP_THUMBNAILS_HELPER' );
		$CROP_THUMBNAILS_HELPER->shouldReceive('getImageSizes')->andReturnUsing( function() use( $cropThumbnailsExists ) {
			if ( $cropThumbnailsExists ) {
				return array(
					'thumbnail2' =>  array (
						'width' => 1170,
						'height' => 600,
					)
				);
			} else {
				return array();
			}
		});

		if ( !$isWpError && $fileReplaceReturn ) {
			if ( !$isNoSize ) {
				// normal way
				$this->assertEquals( $this->meta,
					$class->generateCroppedThumbnails( 330, $file, $this->metaNoCrop, $this->cropMeta )
				);
			} elseif( $cropThumbnailsExists ) {
				$this->assertEquals( $this->meta,
					$class->generateCroppedThumbnails( 330, $file, $this->metaNoSize, $this->cropMeta )
				);
			} else {
				$this->assertEquals( $this->metaNoSizewithCroppingData,
					$class->generateCroppedThumbnails( 330, $file, $this->metaNoSize, $this->cropMeta )
				);
			}
		} else {
			// when some error ocurred, and meta is not updated
			$this->assertEquals( $this->metaNoCrop,
				$class->generateCroppedThumbnails( 330, $file, $this->metaNoCrop, $this->cropMeta )
			);
		}
	}


	/**
	 * @covers MediaLibraryUpdate::mediaLibraryUpdate
	 * @covers MediaLibraryUpdate::deleteOldThumbFile
	 * @uses MediaLibraryUpdate::getCropMetadata
	 * @uses MediaLibraryUpdate::generateCroppedThumbnails
	 * @dataProvider getDataProvider
	 */
	public function testMediaLibraryUpdateCanBePerformed ( $wpAttachmentIsImage, $isMetaEmpty, $isGuidExist, $isFileExist, $isGeneratedMetaEmpty) {
		global $wpdb;
		$file = 'test-image.jpg';
		$meta = $this->meta;
		$metaNoCrop = $this->metaNoCrop;

		//Create  mocks for some methods of class MediaLibraryUpdate that we don't want to use to simplify the test.
		$class = \Mockery::mock('MediaLibraryUpdate')->makePartial();
		$class->shouldReceive('updateAttachmentMetadata')->andReturn( true );
		$class->shouldReceive('isFileExist')->with(Mockery::type('string'))->andReturnUsing( function() use( $isFileExist ) {
			return $isFileExist;
		});

		//Create mocks for other Wordpress functions
		Functions\expect("wp_attachment_is_image")->once()->with( Mockery::type('int') )->andReturnUsing( function() use( $wpAttachmentIsImage ) {
			return $wpAttachmentIsImage;
		});

		Functions\expect("wp_get_attachment_metadata")->atMost()->times(1)->with( Mockery::type('int'), true )->andReturnUsing( function() use( $isMetaEmpty, $meta ) {
			if ($isMetaEmpty) {
				return array();
			} else {
				return $meta;
			}
		});

		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->posts = 'posts';
		$wpdb->shouldReceive( 'get_var' )
			->atMost()
			->times(1)
			->with(Mockery::type('string'))
			->andReturnUsing( function () use( $isGuidExist ) {
				if ( $isGuidExist ) {
					return 'https://example.com/files/2021/12/mont-blanc-5-hiking-to-lac-b-1507730.jpg';
				} else {
					return null;
				}
			});

		Functions\expect("get_option")->with( 'fileupload_url' )->andReturn('https://example.com/bfiles/2021/12/');

		Functions\expect("wp_generate_attachment_metadata")
			->atMost()
			->times(1)
			->with( Mockery::type('int'), Mockery::type('string'), )->andReturnUsing( function() use( $isGeneratedMetaEmpty, $metaNoCrop ) {
				if ( $isGeneratedMetaEmpty ) {
					return array();
				} else {
					return $metaNoCrop;
				}
			});

		Functions\expect("wp_crop_image")
			->atMost()
			->times(2)
			->with(
				Mockery::type('string'),
				Mockery::type('int'),
				Mockery::type('int'),
				Mockery::type('int'),
				Mockery::type('int'),
				Mockery::type('int'),
				Mockery::type('int'),
				false,
				Mockery::type('string')
			)
			->andReturn('test-image-300x300.jpg');


		if ( !$wpAttachmentIsImage || !$isGuidExist || !$isFileExist || $isGeneratedMetaEmpty ) {
			// when some error ocurred
			$this->assertFalse( $class->mediaLibraryUpdate( 330, 1 ) );
		} else {
			// normal way
			$this->assertTrue( $class->mediaLibraryUpdate( 330, 1 ) );
		}
	}


}