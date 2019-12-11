<?php
//defined('KERNEL_INCLUDED') or die('kernel should be included first');

//-----------------------------------------------------------------
/// @file
/// @brief File system abstraction:
/// - #path: paths and filenames manipulation
/// - #BrowseDir: directory browsing
//-----------------------------------------------------------------

/// @cond Function definitions within if/then/else confuse Doxygen

if (!function_exists('fnmatch')) {
	function fnmatch($sSpec, $sFileName)
	{
		$sRegexp = '|^';
		$len = strlen($sSpec);
		$iRangeDepth = 0;
		for ($i = 0; $i < $len; $i++) {
			$c = $sSpec[$i];
			switch ($c) {
				case '?':   $sRegexp .= '.';     break;
				case '*':   $sRegexp .= '.*';    break;
				case '\\':  $sRegexp .= '\\\\';  break;
				case '!':
					$sRegexp .= ($iRangeDepth > 0) ? '^' : '!';
					break;
				case '[':
					$iRangeDepth++;
					$sRegexp .= '[';
					break;
				case ']':
					$iRangeDepth--;
					$sRegexp .= ']';
					break;
				default:
					$sRegexp .= (ctype_alnum($c)) ? $c : '\\' . $c;
					break;
			}
		}
		$sRegexp .= '$|i';
		return regexp::test($sRegexp, $sFileName);
	}
}

/// @endcond


//-----------------------------------------------------------------
/// Paths and filenames manipulation
//-----------------------------------------------------------------

class path
{
	/// @brief Characters that can't be used in a whole file path.
	/// Compatible with Linux and Windows.
	const sInvalidCharsSpec = '\\00-\\037":?*<>|';
	
	/// @brief Characters that can't be used in a file part.
	/// Compatible with Linux and Windows.
	const sPartInvalidCharsSpec = '\\00-\\037":\\?*<>|/\\\\';
	
	
	/// Concat path parts, separing them with '/'.
	/// Remove excedentary '/' and '\' characters.
	/// Accepts either one array argument or any count of string arguments.
	public static function concat($aArg)
	{
		if (!is_array($aArg))
			$aArg = func_get_args();
		
		$aPart = array();
		foreach (array_filter($aArg) as $s)
			$aPart[] = (empty($aPart)) ? $s : ltrim($s, '/\\');
		if (empty($aPart))
			return '';
		
		$iLast = count($aPart) - 1;
		foreach ($aPart as $i => &$s) {
			if ($i > 0)
				$s = ltrim($s, '/\\');
			if ($i < $iLast)
				$s = rtrim($s, '/\\');
		}
		
		return implode('/', $aPart);
	}
	
	
	/// Append a slash to the string, if not already present
	public static function appendSeparator($sPath)
	{
		return (substr($sPath, -1) == '/')
			? $sPath : $sPath . '/';
	}
	
	
	/// Extract the basename of a file
	public static function basename($sPath)
	{
		return basename($sPath);
	}
	
	/// Extract the directory name of a file
	public static function dirname($sPath)
	{
		return dirname($sPath);
	}
	
	
	/// @brief Compute the absolute path of a file or folder.
	/// Expand symbolic links and relative directories.
	/// May return \c false if the path does not point to an existing file or folder.
	public static function absolute($sPath)
	{
		return realpath($sPath);
	}
	
	
	/// @brief Creates a directory. Converts the path to use the right directory separator.
	public static function mkdir($sPath, $iRights = 0777, $bRecursive)
	{
		return mkdir(self::autoUnixWindows($sPath), $iRights, $bRecursive);
	}
	
	
	/// Find the extension of a file. MOREDOC
	/// @param $sPath the file path to examine.
	/// @param $aSuffix contains suffix extensions, like \c .gz or \c .bz2.
	/// If the extension found is a suffix extension, the function tries to find another extension.
	/// @return \c false if none is found, the position of the extension dot otherwise.
	public static function findExtension($sPath, array $aSuffix = array())
	{
		$sLastChar = substr($sPath, -1);
		if ($sLastChar == '/' || $sLastChar == '\\')
			return false;
		
		$sName = basename($sPath);
		$lenName = $iBest = strlen($sName);
		do{
			$i = strrpos($sName, '.', $iBest - $lenName - 1);
			if ($i === false)
				break;
			$iBest = $i;
		}while (in_array(substr($sName, $i), $aSuffix));
		return ($iBest == $lenName) ? false : $iBest + strlen($sPath) - $lenName;
	}
	
	
	/// Split a file around its extension. MOREDOC
	/// @return a 2 elements array containing the basename and the dotted extension.
	public static function splitExtension($sPath, array $aSuffix = array())
	{
		$i = self::findExtension($sPath, $aSuffix);
		return ($i === false)
			? array($sPath, '')
			: array(substr($sPath, 0, $i), substr($sPath, $i));
	}
	
	
	/// Extract the extension of a file, without its dot.
	public static function getExtension($sPath, array $aSuffix = array())
	{
		$i = self::findExtension($sPath, $aSuffix);
		return ($i === false) ? '' : substr($sPath, $i + 1);
	}
	
	/// Extract the extension of a file, with its dot.
	public static function getExtensionDotted($sPath, array $aSuffix = array())
	{
		$i = self::findExtension($sPath, $aSuffix);
		return ($i === false) ? '' : substr($sPath, $i);
	}
	
	
	/// Remove the extension of a file
	public static function stripExtension($sPath, $aSuffix = array())
	{
		$i = self::findExtension($sPath, $aSuffix);
		return ($i === false) ? $sPath : rtrim(substr($sPath, 0, $i), '.');
	}
	
	
	/// Convert a path to Unix style: \c '/' separators only.
	public static function unix($s)
	{
		return str_replace('\\', '/', $s);
	}
	
	/// Convert a path to Windows style: \c '\\' separators only.
	public static function windows($s)
	{
		return str_replace('/', '\\', $s);
	}
	
	/// Convert a path to Unix or Windows style, depending of the #bWindows constant.
	public static function autoUnixWindows($s)
	{
		return (bWindows) ? self::windows($s) : self::unix($s);
	}
	
	
	/// Remove double separators, convert all separators into Unix style (\c '/').
	public static function cleanSeparators($sPath)
	{
		return regexp::replace('#[\\\\/]+#', '/', $sPath);
	}
	
	
	
	
	/// Indicate whether a file name is <tt>'.'</tt> or <tt>'..'</tt> pseudo-directory.
	public static function isDots($sFile)
	{
		return $sFile == '.' || $sFile == '..';
	}
	
	
	/// Indicate whether a directory contains at least one subdirectory.
	public static function containsSubDir($sPath)
	{
		$dir = BrowseDir::start($sPath);
		$bContainsSubDir = false;
		if ($dir != null) {
			while ($dir->read())
				if ($bContainsSubDir = is_dir($dir->getPath()))
					break;
			$dir->close();
		}
		return $bContainsSubDir;
	}
	
	
	/// Wrapper to \c fnmatch, works under all platforms.
	function match($sSpec, $sFileName)
	{
		return fnmatch($sSpec, $sFileName);
	}
	
	
	/// Create an empty temporary file and return its name. MOREDOC
	/// @param $sPrefix the prefix to use for the temporary file name.
	/// @param $sDir the directory where to create the temporary file.
	/// If it does not exist, use the default temporary directory.
	public static function getTempFileName($sPrefix, $sDir = '?')
	{
		return tempnam($sDir, $sPrefix);
	}
	
	/// Delete all temporary files in a directory having a given prefix and older than a given age. MOREDOC
	/// @param $sPrefix the prefix of the temporary files to delete.
	/// @param $sDir the directory containing the temporary files to delete.
	/// @param $iAgeInSeconds the minimum age for the temporary files to be deleted, in seconds.
	/// The default minimum age is one day.
	public static function cleanTempFiles($sPrefix, $sDir, $iAgeInSeconds = 86400)
	{
		$dtModifThreshold = time() - $iAgeInSeconds;
		foreach (glob(path::concat($sDir, $sPrefix . '*'), glob::NOSORT) as $sTempFilePath) {
			$dtModif = @filemtime($sTempFilePath);
			if ($dtModif < $dtModifThreshold)
				@unlink($sTempFilePath);
		}
	}
}



//-----------------------------------------------------------------
/// Directory browsing
//-----------------------------------------------------------------

class BrowseDir
{
	protected $dir;
	protected $sName;
	
	protected function __construct($dir)
	{
		$this->dir   = $dir;
		$this->sName = null;
	}
	
	
	/// Start browsing a directory. MOREDOC
	/// @return \c null if error.
	public static function start($sDir)
	{
		$dir = dir($sDir);
		return ($dir !== false)
			? new BrowseDir($dir)
			: null;
	}
	
	/// Read the next entry of the directory, excluding \c '.' and \c '..'. MOREDOC
	/// @return \c true if an entry has been read,
	/// \c false if no more entry is available.
	public function read()
	{
		if ($this->dir == null)
			return false;
		do
			$sName = $this->dir->read();
		while (path::isDots($sName));
		
		if ($sName === false) {
			$this->close();
			return false;
		}
		
		$this->sName = $sName;
		return true;
	}
	
	/// Finish directory browsing.
	public function close()
	{
		if ($this->dir != null) {
			$this->dir->close();
			$this->dir = $this->sName = null;
		}
	}
	
	
	/// Return the current file name.
	public function getName() { return $this->sName; }
	
	/// Return the current file full path.
	public function getPath() { return path::concat($this->dir->path, $this->sName); }
	
	/// Indicate whether the current entry is a directory (\c true) or a file (\c false).
	public function isDir() { return is_dir($this->getPath()); }

}


//-----------------------------------------------------------------
/// More
//-----------------------------------------------------------------

abstract class morepath
{
   // Auxiliary function to recursively delete a directory
   public static function deleteDirectoryRecursively($dir) 
   {
      if (!file_exists($dir)) return true;
      if (!is_dir($dir)) return unlink($dir);
      foreach (scandir($dir) as $item) {
         if ($item == '.' || $item == '..') continue;
         if (!self::deleteDirectoryRecursively($dir.'/'.$item)) return false;
          //DIRECTORY_SEPARATOR
      }
      return rmdir($dir);
   }

   // Auxiliary function to delete the files in a directory
   public static function deleteDirectory($dir) 
   {
      if (!is_dir($dir)) return false;
      $r = true;
      foreach (scandir($dir) as $item) {
         if ($item == '.' || $item == '..') continue;
         $file = $dir.'/'.$item;
         if (! is_dir($file))
         {
            $ri = unlink($file);
            $r = $r && $ri;
         }
      }
      return $r;
   }

   // Auxiliary function to safely read files
   public static function file_safe_get_contents($sPath) 
   {
      if (!is_file($sPath)) 
         throw new ProcessException("File not found ".$sPath);
      return file_get_contents($sPath);
   }  

}

/// Delete a FAT entry: a file or a directory recursively. MOREDOC
/// @return \c true if success (file has been deleted successfully
/// or did not exist), \c false otherwise (file still exists).
function deleteRecurse($sFile)
{
   if (is_link($sFile))
      return unlink($sFile);
   
   if (!file_exists($sFile))
      return true;
   
   if (!is_dir($sFile))
      return unlink($sFile);
   
   $dir = BrowseDir::start($sFile);
   if ($dir != null)
      while ($dir->read())
         deleteRecurse($dir->getPath());
   
   return rmdir($sFile);
}
?>