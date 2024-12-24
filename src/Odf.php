<?php

declare(strict_types=1);

namespace Odtphp;

use Odtphp\Exceptions\OdfException;
use Odtphp\Zip\PclZipProxy;
use Odtphp\Zip\PhpZipProxy;
use Odtphp\Zip\ZipInterface;
use Odtphp\Segment;
use Odtphp\AllowDynamicProperties;

/**
 * ODT (OpenDocument Text) templating class.
 *
 * This class provides functionality to generate and manipulate ODT documents
 * through templating. It supports variable substitution, segment management,
 * and image handling within ODT files.
 *
 * @license GPL
 * @copyright 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska
 */
#[AllowDynamicProperties]
class Odf {
  /**
   * Zip file handler.
   *
   * @var \Odtphp\Zip\ZipInterface
   */
  protected ZipInterface $file;

  /**
   * Conversion ratio from pixels to centimeters.
   */
  protected const PIXEL_TO_CM = 0.026458333;

  /**
   * Default configuration values.
   *
   * @var array<string, mixed>
   */
  protected array $config = [
        'ZIP_PROXY' => PhpZipProxy::class,
        'DELIMITER_LEFT' => '{',
        'DELIMITER_RIGHT' => '}',
        'PATH_TO_TMP' => NULL
    ];

  /**
   * Content of the content.xml file.
   */
  protected string $contentXml = '';

  /**
   * Content of the manifest.xml file.
   */
  protected string $manifestXml = '';

  /**
   * Content of the styles.xml file.
   */
  protected string $stylesXml = '';

  /**
   * Temporary file path.
   */
  protected string $tmpfile = '';

  /**
   * Array of images used in the document.
   *
   * @var array<string, mixed>
   */
  protected array $images = [];

  /**
   * Template variables.
   *
   * @var array<string, mixed>
   */
  protected array $vars = [];

  /**
   * Manifest variables for image tracking.
   *
   * @var array<string, string>
   */
  protected array $manifestVars = [];

  /**
   * Document segments.
   *
   * @var array<int, Segment>
   */
  protected array $segments = [];

  /**
   * Initialize ODT document handling.
   *
   * @param string $filename
   *   Path to the ODT template file to process.
   * @param array<string, mixed> $config
   *   Configuration options for document processing.
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   When the ODT file cannot be initialized or processed.
   */
  public function __construct(
    protected readonly string $filename,
    array $config = [],
  ) {
    // Merge and validate configuration.
    $this->config = $this->mergeAndValidateConfig($config);

    // Set default temporary directory if not provided.
    if ($this->config['PATH_TO_TMP'] === NULL) {
      $this->config['PATH_TO_TMP'] = sys_get_temp_dir();
    }

    // Validate configuration components.
    $this->validateTemporaryDirectory();
    $this->validateZipProxy();

    // Initialize properties and process file.
    $this->initializeProperties();
    $this->processZipFile();
  }

  /**
   * Merge and validate configuration options.
   *
   * @param array<string, mixed> $config
   *   User-provided configuration.
   *
   * @return array<string, mixed>
   *   Validated configuration array with default values merged.
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   If configuration is invalid or cannot be processed.
   */
  private function mergeAndValidateConfig(array $config): array {
    // Start with default configuration.
    $mergedConfig = $this->config;

    // Merge user configuration.
    foreach ($config as $key => $value) {
      $mergedConfig[$key] = $value;
    }

    return $mergedConfig;
  }

  /**
   * Validate temporary directory configuration.
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   If the temporary directory is invalid or inaccessible.
   */
  private function validateTemporaryDirectory(): void {
    $path = $this->config['PATH_TO_TMP'];

    if (!is_string($path)) {
      throw new OdfException('Temporary directory path must be a string');
    }

    $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if (!is_dir($path)) {
      throw new OdfException('Temporary directory does not exist');
    }

    if (!is_writable($path)) {
      throw new OdfException('Temporary directory is not writable');
    }

    $this->config['PATH_TO_TMP'] = $path;
  }

  /**
   * Validate ZIP proxy configuration.
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   If the ZIP proxy class is invalid or does not implement the required interface.
   *
   * @return void
   *   Validates the ZIP proxy class configuration.
   */
  private function validateZipProxy(): void {
    $zipProxyClass = $this->config['ZIP_PROXY'];

    if (!class_exists($zipProxyClass)) {
      throw new OdfException("ZIP proxy class does not exist: $zipProxyClass");
    }

    if (!is_subclass_of($zipProxyClass, ZipInterface::class)) {
      throw new OdfException("$zipProxyClass must implement ZipInterface");
    }
  }

  /**
   * Initialize object properties with default values.
   *
   * @return void
   *   Initializes all internal object properties to their default values.
   */
  private function initializeProperties(): void {
    $this->contentXml = '';
    $this->manifestXml = '';
    $this->stylesXml = '';
    $this->tmpfile = '';
    $this->images = [];
    $this->vars = [];
    $this->manifestVars = [];
    $this->segments = [];
  }

  /**
   * Process the ZIP file and extract necessary XML contents.
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   If the file cannot be processed or XML contents cannot be extracted.
   *
   * @return void
   *   Processes the ZIP file and extracts required XML contents.
   */
  private function processZipFile(): void {
    // Validate file existence.
    if (!file_exists($this->filename)) {
      throw new OdfException("File '{$this->filename}' does not exist");
    }

    // Create ZIP handler.
    $zipHandlerClass = $this->config['ZIP_PROXY'];
    $this->file = new $zipHandlerClass($this->config['PATH_TO_TMP']);

    // Open ZIP file.
    if ($this->file->open($this->filename) !== TRUE) {
      throw new OdfException("Error opening file '{$this->filename}'");
    }

    // Extract XML contents.
    $this->extractXmlContents();

    // Close the ZIP file.
    $this->file->close();

    // Create a temporary copy of the file.
    $this->createTemporaryFileCopy();

    // Process row segments.
    $this->moveRowSegments();
  }

  /**
   * Extract XML contents from the ZIP file.
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   If XML content extraction fails or required files are missing.
   *
   * @return void
   *   Extracts and stores XML content from the ODT file.
   */
  private function extractXmlContents(): void {
    // Extract content.xml.
    $this->contentXml = $this->file->getFromName('content.xml');
    if ($this->contentXml === FALSE) {
      throw new OdfException("Error during content.xml extraction");
    }

    // Extract manifest.xml.
    $this->manifestXml = $this->file->getFromName('META-INF/manifest.xml');
    if ($this->manifestXml === FALSE) {
      throw new OdfException("Error during manifest.xml extraction");
    }

    // Extract styles.xml.
    $this->stylesXml = $this->file->getFromName('styles.xml');
    if ($this->stylesXml === FALSE) {
      throw new OdfException("Error during styles.xml extraction");
    }
  }

  /**
   * Create a temporary copy of the file for processing.
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   If the temporary file cannot be created or copied.
   *
   * @return void
   *   Creates a temporary copy of the ODT file.
   */
  private function createTemporaryFileCopy(): void {
    $this->tmpfile = tempnam($this->config['PATH_TO_TMP'], 'odtphp_');
    if ($this->tmpfile === FALSE) {
      throw new OdfException('Error creating temporary file');
    }
    copy($this->filename, $this->tmpfile);
  }

  /**
   * Delete the temporary file when the object is destroyed.
   *
   * @return void
   *   Cleans up temporary files used during processing.
   */
  public function __destruct() {
    if (file_exists($this->tmpfile)) {
      unlink($this->tmpfile);
    }
  }

  /**
   * Assign a template variable.
   *
   * @param string $key
   *   Name of the variable within the template.
   * @param string $value
   *   Replacement value for the variable.
   * @param bool $encode
   *   Whether to encode special XML characters for safe XML output.
   * @param string $charset
   *   Character set encoding of the input value (defaults to UTF-8).
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   When the variable is not found in the document.
   *
   * @return $this
   *   The current ODT object for method chaining.
   */
  public function setVars($key, $value, $encode = TRUE, $charset = 'UTF-8'): self {
    $tag = $this->config['DELIMITER_LEFT'] . $key . $this->config['DELIMITER_RIGHT'];
    if (strpos($this->contentXml, $tag) === FALSE && strpos($this->stylesXml, $tag) === FALSE) {
      throw new OdfException("var $key not found in the document");
    }

    // Handle encoding.
    $value = $encode ? $this->recursiveHtmlspecialchars($value) : $value;

    // Convert to UTF-8 if not already.
    if ($charset !== 'UTF-8') {
      $value = mb_convert_encoding($value, 'UTF-8', $charset);
    }

    $this->vars[$tag] = str_replace("\n", "<text:line-break/>", $value);
    return $this;
  }

  /**
   * Set the value of a variable in a template.
   *
   * @param string $key
   *   Name of the variable within the template.
   * @param string $value
   *   Replacement value for the variable.
   * @param bool $encode
   *   Whether to encode special XML characters for safe XML output.
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   When the variable is not found in the document.
   *
   * @return $this
   *   The current ODT object for method chaining.
   */
  public function setVariable($key, $value, $encode = TRUE): self {
    return $this->setVars($key, $value, $encode);
  }

  /**
   * Check if a variable exists in the template.
   *
   * @param string $key
   *   Name of the variable to check for in the template.
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   When the variable check operation fails.
   *
   * @return bool
   *   TRUE if the variable exists in the document, FALSE otherwise.
   */
  public function variableExists($key): bool {
    return strpos($this->contentXml, $this->config['DELIMITER_LEFT'] . $key . $this->config['DELIMITER_RIGHT']) !== FALSE;
  }

  /**
   * Assign a template variable as a picture.
   *
   * @param string $key
   *   Name of the variable within the template.
   * @param string $value
   *   Absolute or relative path to the picture file.
   * @param int $page
   *   Page number to anchor the image to (-1 for as-char anchoring).
   * @param int|null $width
   *   Width of the picture in pixels (null to keep original).
   * @param int|null $height
   *   Height of the picture in pixels (null to keep original).
   * @param int|null $offsetX
   *   Horizontal offset in pixels (ignored if $page is -1).
   * @param int|null $offsetY
   *   Vertical offset in pixels (ignored if $page is -1).
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   When the image cannot be added or processed.
   *
   * @return $this
   *   The current ODT object for method chaining.
   */
  public function setImage($key, $value, $page = -1, $width = NULL, $height = NULL, $offsetX = NULL, $offsetY = NULL): self {
    $filename = strtok(strrchr($value, '/'), '/.');
    $file = substr(strrchr($value, '/'), 1);
    $size = @getimagesize($value);
    if ($size === FALSE) {
      throw new OdfException("Invalid image");
    }
    if (!$width && !$height) {
      [$width, $height] = $size;
      $width *= $this->getPixelToCm();
      $height *= $this->getPixelToCm();
    }
    $anchor = $page == -1 ? 'text:anchor-type="as-char"' : "text:anchor-type=\"page\" text:anchor-page-number=\"{$page}\" svg:x=\"{$offsetX}cm\" svg:y=\"{$offsetY}cm\"";
    $xml = <<<IMG
<draw:frame draw:style-name="fr1" draw:name="$filename" {$anchor} svg:width="{$width}cm" svg:height="{$height}cm" draw:z-index="3"><draw:image xlink:href="Pictures/$file" xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/></draw:frame>
IMG;

    $this->images[$value] = $file;
    $this->manifestVars[] = $file;
    $this->setVars($key, $xml, FALSE);
    return $this;
  }

  /**
   * Move segment tags for lines of tables.
   *
   * Called automatically within the constructor.
   *
   * @return void
   *   Modifies the internal XML content by moving segment tags.
   */
  private function moveRowSegments(): void {
    // Search all possible rows in the document.
    $reg1 = "#<table:table-row[^>]*>(.*)</table:table-row>#smU";
    preg_match_all($reg1, $this->contentXml, $matches);
    for ($i = 0, $size = count($matches[0]); $i < $size; $i++) {
      // Check if the current row contains a segment row.*.
      $reg2 = '#\[!--\sBEGIN\s(row.[\S]*)\s--\](.*)\[!--\sEND\s\\1\s--\]#smU';
      if (preg_match($reg2, $matches[0][$i], $matches2)) {
        $balise = str_replace('row.', '', $matches2[1]);
        // Move segment tags around the row.
        $replace = [
              '[!-- BEGIN ' . $matches2[1] . ' --]'   => '',
              '[!-- END ' . $matches2[1] . ' --]'     => '',
              '<table:table-row'                          => '[!-- BEGIN ' . $balise . ' --]<table:table-row',
              '</table:table-row>'                        => '</table:table-row>[!-- END ' . $balise . ' --]'
          ];
        $replacedXML = str_replace(array_keys($replace), array_values($replace), $matches[0][$i]);
        $this->contentXml = str_replace($matches[0][$i], $replacedXML, $this->contentXml);
      }
    }
  }

  /**
   * Merge template variables.
   *
   * Called automatically for a save operation.
   *
   * @return void
   *   Processes and updates the internal XML content with merged variables.
   */
  private function parse(): void {
    $this->contentXml = str_replace(array_keys($this->vars), array_values($this->vars), $this->contentXml);
    $this->stylesXml  = str_replace(array_keys($this->vars), array_values($this->vars), $this->stylesXml);
  }

  /**
   * Add the merged segment to the document.
   *
   * @param \Odtphp\Segment $segment
   *   The segment to merge.
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   When the segment cannot be merged or has not been set.
   *
   * @return $this
   *   The current ODT object instance.
   */
  public function mergeSegment(Segment $segment): self {
    if (!array_key_exists($segment->getName(), $this->segments)) {
      throw new OdfException($segment->getName() . ' cannot be parsed, has it been set yet?');
    }
    $string = $segment->getName();
    $reg = '@\[!--\sBEGIN\s' . $string . '\s--\](.*)\[!--.+END\s' . $string . '\s--\]@smU';
    $this->contentXml = preg_replace($reg, $segment->getXmlParsed(), $this->contentXml);
    foreach ($segment->manifestVars as $val) {
      // Copy all segment image names into current array.
      $this->manifestVars[] = $val;
    }
    return $this;
  }

  /**
   * Display all the current template variables.
   *
   * @return string
   *   The formatted string containing all template variables.
   */
  public function printVars(): string {
    return print_r('<pre>' . print_r($this->vars, TRUE) . '</pre>', TRUE);
  }

  /**
   * Display the XML content of the file from ODT document as it is at the moment.
   *
   * @return string
   *   The XML content of the ODT document.
   */
  public function __toString(): string {
    return $this->contentXml;
  }

  /**
   * Display loop segments declared with setSegment().
   *
   * @return string
   *   Space-separated list of declared segments.
   */
  public function printDeclaredSegments(): string {
    return '<pre>' . print_r(implode(' ', array_keys($this->segments)), TRUE) . '</pre>';
  }

  /**
   * Check if the specified segment exists in the document.
   *
   * @param string $segment
   *   The name of the segment to check.
   *
   * @return bool
   *   TRUE when segment exists, FALSE otherwise.
   */
  public function segmentExists($segment): bool {
    $reg = "#\[!--\sBEGIN\s$segment\s--](.*?)\[!--\sEND\s$segment\s--]#smU";
    return preg_match($reg, html_entity_decode($this->contentXml), $m) != 0;
  }

  /**
   * Declare a segment in order to use it in a loop.
   *
   * @param string $segment
   *   The name of the segment to declare.
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   When the segment cannot be found in the document.
   *
   * @return \Odtphp\Segment
   *   The requested segment object for use in a loop.
   */
  public function setSegment($segment): Segment {
    if (array_key_exists($segment, $this->segments)) {
      return $this->segments[$segment];
    }
    $reg = "#\[!--\sBEGIN\s$segment\s--\](.*?)\[!--\sEND\s$segment\s--\]#smU";
    if (preg_match($reg, html_entity_decode($this->contentXml), $m) == 0) {
      throw new OdfException("'$segment' segment not found in the document");
    }
    $this->segments[$segment] = new Segment($segment, $m[1], $this);
    return $this->segments[$segment];
  }

  /**
   * Save the ODT file to disk.
   *
   * @param string|null $file
   *   Name of the desired file. If null, uses the original filename.
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   When the file cannot be saved to disk.
   *
   * @return void
   *   Saves the ODT file to the specified location.
   */
  public function saveToDisk($file = NULL): void {
    $this->saveInternal();
    if ($file === NULL) {
      $file = $this->filename;
    }
    copy($this->tmpfile, $file);
  }

  /**
   * Export the file as an attached file via HTTP.
   *
   * @param string $name
   *   Optional name for the downloaded file.
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   When the file cannot be exported or sent via HTTP.
   *
   * @return void
   *   Sends the ODT file as an HTTP attachment.
   */
  public function exportAsAttachedFile($name = ""): void {
    $this->saveInternal();
    if (empty($name)) {
      $name = basename($this->filename);
    }
    header('Content-type: application/vnd.oasis.opendocument.text');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    readfile($this->tmpfile);
  }

  /**
   * Save internal ODT file state.
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   When the internal file state cannot be saved.
   *
   * @return void
   *   Updates the internal ODT file state with current changes.
   */
  private function saveInternal(): void {
    $this->file->open($this->tmpfile);
    $this->parse();
    if (!$this->file->addFromString('content.xml', $this->contentXml) || !$this->file->addFromString('styles.xml', $this->stylesXml)) {
      throw new OdfException('Error during file export addFromString');
    }
    // Find second last newline in the manifest.xml file.
    $lastpos = strrpos($this->manifestXml, "\n", -15);
    $manifdata = "";

    // Enter all images description in $manifdata variable.
    foreach ($this->manifestVars as $val) {
      $ext = substr(strrchr($val, '.'), 1);
      $manifdata = $manifdata . '<manifest:file-entry manifest:media-type="image/' . $ext . '" manifest:full-path="Pictures/' . $val . '"/>' . "\n";
    }

    // Place content of $manifdata variable in manifest.xml file at appropriate place.
    $replace = '<manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/>';
    if ((strlen($manifdata) > 0) && (strpos($this->manifestXml, $replace) !== FALSE)) {
      $this->manifestXml = str_replace($replace,
        $replace . "\n" . $manifdata, $this->manifestXml);
    }
    else {
      // This branch is a fail-safe but normally should not be used.
      $this->manifestXml = substr_replace($this->manifestXml, "\n" . $manifdata, $lastpos + 1, 0);
    }
    if (!$this->file->addFromString('META-INF/manifest.xml', $this->manifestXml)) {
      throw new OdfException('Error during manifest file export');
    }
    foreach ($this->images as $imageKey => $imageValue) {
      $this->file->addFile($imageKey, 'Pictures/' . $imageValue);
    }
    // Seems to bug on windows CLI sometimes.
    $this->file->close();
  }

  /**
   * Returns a variable of configuration.
   *
   * @param string $configKey
   *   The name of the configuration variable to retrieve.
   *
   * @return string
   *   The requested configuration value.
   */
  public function getConfig($configKey): string {
    if (array_key_exists($configKey, $this->config)) {
      return $this->config[$configKey];
    }
    return FALSE;
  }

  /**
   * Get the current configuration.
   *
   * @return array<string, mixed>
   *   The complete configuration array.
   */
  public function getAllConfig(): array {
    return $this->config;
  }

  /**
   * Returns the temporary working file.
   *
   * @return string
   *   The path to the temporary working file.
   */
  public function getTmpfile(): string {
    return $this->tmpfile;
  }

  /**
   * Get the pixel to centimeter conversion ratio.
   *
   * @return float
   *   The pixel to centimeter conversion ratio.
   */
  public function getPixelToCm(): float {
    return self::PIXEL_TO_CM;
  }

  /**
   * Recursive htmlspecialchars.
   *
   * @param mixed $value
   *   The value to convert.
   *
   * @return mixed
   *   The converted value.
   */
  protected function recursiveHtmlspecialchars($value): mixed {
    if (is_array($value)) {
      return array_map([$this, 'recursiveHtmlspecialchars'], $value);
    }
    else {
      return htmlspecialchars($value);
    }
  }

}
