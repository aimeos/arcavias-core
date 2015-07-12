<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2011
 * @license LGPLv3, http://www.gnu.org/licenses/lgpl.html
 * @package MW
 * @subpackage Jsb2
 */


/**
 *
 * Generates compressed JS files read from a .jsb2 package.
 *
 * @package MW
 * @subpackage Jsb2
 */
class MW_Jsb2_Default
{
	private $_registeredPackages = array();
	private $_baseURL = '';
	private $_basePath = '';
	private $_deployDir = '';


	/**
	 * Initializes the Jsb2 object.
	 *
	 * @param string $filename Path to manifest file
	 * @param string $baseURL Base URL for HTML output
	 * @param array $filter Which packages  schould NOT be returned
	 */
	public function __construct( $filename, $baseURL = "", $filter = array() )
	{
		$manifest = $this->_getManifest( $filename );

		$this->_baseURL = rtrim( $baseURL, '/' ) . '/';
		$this->_basePath = dirname( $filename ) . '/';
		$this->_deployDir = $manifest->deployDir . '/';

		$this->_registeredPackages = $this->_getPackages( $manifest, $filter );
	}


	/**
	 * Returns the list of URLs for packages files with given filter.
	 *
	 * @param string $type Specific filetypes to create output
	 * @param strig $version URL version string with %s placeholder for the file time
	 * @return array List of URLs for the package files
	 */
	public function getUrls( $type, $version = '?v=%s' )
	{
		$files = array();

		foreach ( $this->_registeredPackages as $filetype => $packageList )
		{
			if( $filetype !== $type ) {
				continue;
			}

			foreach( $packageList as $package )
			{
				$packageFile = $this->_deployDir . $package->file;
				$packageFileFilesystem = $this->_basePath . $packageFile;
				$packageFileTime = 0;
				$timestamp = 0;

				if( is_file( $packageFileFilesystem ) ) {
					$packageFileTime = filemtime( $packageFileFilesystem );
				}

				$result = $this->_getFileUrls( $this->_baseURL, $this->_basePath, $package, $timestamp, $version );

				if( $packageFileTime >= $timestamp ) {
					$files[] = $this->_baseURL . $packageFile . sprintf( $version, $packageFileTime );
				} else {
					$files = array_merge( $files, $result );
				}
			}
		}

		return $files;
	}


	/**
	 * Returns HTML for packages files with given filter.
	 *
	 * @param string $type Specific filetypes to create output
	 * @return string HTML output with script and stylesheet link tags
	 */
	public function getHTML( $type )
	{
		$html = '';
		$version = '?v=%s';

		if( strpos( $this->_baseURL, '?' ) !== false ) {
			$version = '&v=%s';
		}

		foreach( $this->getUrls( $type, $version ) as $file )
		{
			switch( $type )
			{
				case 'js':
					$html .= '<script type="text/javascript" src="' . $file . '"></script>' . PHP_EOL;
					break;
				case 'css':
					$html .= '<link rel="stylesheet" type="text/css" href="' . $file . '"/>' . PHP_EOL;
					break;
			}
		}

		return $html;
	}


	/**
	 * Creates minified packages files.
	 *
	 * @param string $type Specific filetypes to create output
	 * @param boolean $debug If true no compression is applied to the files
	 * @param integer $filepermission Set permissions for created package files
	 * @param integer $dirpermission Set permissions for created directorys
	 */
	public function deploy( $type = null, $debug = true, $filepermission = 0644, $dirpermission = 0755 )
	{
		foreach( $this->_registeredPackages as $filetype => $packageFiles )
		{
			if( $type !== null && $filetype !== $type ) {
				continue;
			}

			foreach( $packageFiles as $package)
			{
				$packageFile = $this->_basePath . $this->_deployDir . $package->file;

				$packageDir = dirname( $packageFile );

				if( !is_dir( $packageDir ) )
				{
					if( mkdir( $packageDir, $dirpermission, true ) === false ) {
						throw new MW_Jsb2_Exception( sprintf( 'Unable to create path for package file "%1$s"', $packageDir ) );
					}
				}

				$this->_minify( $package, $debug, $filepermission );
			}
		}
	}


	/**
	 * Returns the file URLs of the given package object.
	 *
	 * @param string $baseUrl URL the file location is relative to
	 * @param string $basePath Absolute path to the base directory of the files
	 * @param stdClass $package Object with "fileIncludes" property containing a
	 * 	list of file objects with "path" and "text" properties
	 * @param integer &$timestamp Value/result parameter that will contain the latest file modification timestamp
	 * @throws MW_Jsb2_Exception If the file modification timestamp couldn't be determined
	 */
	protected function _getFileUrls( $baseUrl, $basePath, stdClass $package, &$timestamp, $version = '?v=%s' )
	{
		$timestamp = (int) $timestamp;
		$filesToDisplay = array();

		foreach( $package->fileIncludes as $singleFile )
		{
			$filename = $basePath . $singleFile->path . $singleFile->text;

			if( !is_file( $filename ) || ( $fileTime = filemtime( $filename ) ) === false ) {
				throw new MW_Jsb2_Exception( sprintf( 'Unable to read filetime of file "%1$s"', $filename ) );
			}

			$timestamp = max( $timestamp, $fileTime );
			$filesToDisplay[] = $baseUrl . $singleFile->path . $singleFile->text . sprintf( $version, $timestamp );
		}

		return $filesToDisplay;
	}


	/**
	 * Creates minified file for given package using JSMin.
	 *
	 * @param object $package Package object from manifest to minify
	 * @param boolean $debug Create debug files if true
	 * @param integer $permissions File permissions to set on new files
	 */
	protected function _minify( $package, $debug, $permissions )
	{
		$content = '';

		foreach( $this->_getFilenames( $package, $this->_basePath ) as $filename )
		{
			if( ( $content .= file_get_contents( $filename ) ) === false ) {
				throw new MW_Jsb2_Exception( sprintf( 'Unable to get content of file "%1$s"', $filename ) );
			}
		}

		if( $debug !== true ) {
			$content = JSMin::minify( $content );
		}

		$pkgFileName = $this->_basePath . $this->_deployDir . $package->file;

		if( file_put_contents( $pkgFileName, $content ) === false ) {
			throw new MW_Jsb2_Exception( sprintf( 'Unable to create package file "%1$s"', $pkgFileName ) );
		}

		if( chmod( $pkgFileName, $permissions ) === false ) {
			throw new MW_Jsb2_Exception( sprintf( 'Unable to change permissions of file "%1$s"', $pkgFileName ) );
		}
	}


	/**
	 * Get the packages from a JSON decoded manifest and validates them.
	 *
	 * @param object JSON decoded manifest
	 * @param array $filter What packages should NOT be returned
	 */
	protected function _getPackages( $manifest, $filter = array() )
	{
		$packageContainer = array();

		if( !isset( $manifest->pkgs ) || !is_array( $manifest->pkgs ) ) {
			throw new MW_Jsb2_Exception( 'No packages found' );
		}

		foreach( $manifest->pkgs as $package )
		{
			if( !isset( $package->name ) || !isset( $package->file ) || !is_object( $package ) ) {
				throw new MW_Jsb2_Exception( 'Invalid package content' );
			}

			if( !isset( $package->fileIncludes ) || !is_array( $package->fileIncludes ) ) {
				throw new MW_Jsb2_Exception( 'No files in package found' );
			}

			if( !in_array( $package->name, $filter ) ) {
				$packageContainer[ pathinfo( $package->file, PATHINFO_EXTENSION ) ][] = $package;
			}
		}

		return $packageContainer;
	}


	/**
	 * Gets files stored in package an checkes for existence.
	 *
	 * @param object $package Single package from manifest
	 * @param string $prePath String added before filepaths
	 */
	protected function _getFilenames( $package, $prePath = '' )
	{
		$filenames = array();

		foreach( $package->fileIncludes as $include )
		{
			if( !is_object( $include ) ) {
				throw new MW_Jsb2_Exception( 'Invalid file inlcude' );
			}

			$filename = $include->path . $include->text;
			$absfilename = $this->_basePath . $filename;

			if( !file_exists( $absfilename ) ) {
				throw new MW_Jsb2_Exception( sprintf( 'File does not exists: "%1$s"', $absfilename ) );
			}

			$filenames[] = $prePath . $filename;
		}

		return $filenames;
	}


	/**
	 * Returns the content of a manifest file.
	 *
	 * @param string $filepath Path to manifest
	 * @throws MW_Jsb2_Exception
	 */
	protected function _getManifest( $filepath )
	{
		if( !file_exists( $filepath ) ) {
			throw new MW_Jsb2_Exception( sprintf( 'File does not exists: "%1$s"', $filepath ) );
		}

		if( ( $content = file_get_contents( $filepath ) ) === false ) {
			throw new MW_Jsb2_Exception( sprintf( 'Unable to read content from "%1$s"', $filepath ) );
		}

		if( ( $content = json_decode( $content ) ) === null ) {
			throw new MW_Jsb2_Exception( 'File content is not JSON encoded' );
		}

		return $content;
	}
}