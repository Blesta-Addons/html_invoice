<?php
/**
 * Default Invoice Template
 *
 * @package blesta
 * @subpackage blesta.components.invoice_templates.templates.default
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
// Use the Default Invoice PDF renderer for generating PDFs
Loader::load(dirname(__FILE__) . DS . "html_invoice_pdf.php");
Loader::load(dirname(__FILE__) . DS . "html_invoice_htm.php");

class HtmlInvoice extends InvoiceTemplate {


	/**
	 * @var string The version of this template
	 */
	private static $version = "1.5.0";
	/**
	 * @var string The authors of this template
	 */
	private static $authors = array(array('name'=>"Mohamed Anouar Achoukhy",'url'=>"http://www.naja7host.com"));
	/**
	 * @var HtmlInvoicePdf The PDF object used for rendering
	 */
	private $pdf;
	private $html ;

	/**
	 * @var array An array of meta data for this template
	 */
	private $meta = array();
	/**
	 * @param stdClass Invoice data for the last invoice set
	 */
	private $invoice = array();
	/**
	 * @param string MIME type to use when rendering this document
	 */
	private $mime_type;
	
	/**
	 * Loads the language to be used for this invoice
	 */
	public function __construct() {		
		// Load language for this template
		Language::loadLang("html_invoice", null, dirname(__FILE__) . DS . "language" . DS);
	
	}

	/**
	 * Sets the meta data to use for this invoice. This method is invoked after
	 * __construct() but before makeDocument()
	 *
	 * @param array $meta An array of meta data including:
	 * 	-background The absolute path to the background graphic
	 * 	-logo The absolute path to the logo graphic
	 * 	-company_name The name of the company
	 * 	-company_address The address of the company
	 * 	-terms The terms to display on this invoice
	 * 	-paper_size The size of paper to use (e.g. "A4" or "Letter")
	 * 	-tax An array of tax info including:
	 * 		-tax_id The Tax ID/VATIN of this company
	 * 		-cascade_tax Whether or not taxes are cascading
	 */
	public function setMeta($meta) {
		$this->meta = $meta;
		
		$font = isset($this->meta['settings']['inv_font_' . Configure::get("Blesta.language")]) ? $this->meta['settings']['inv_font_' . Configure::get("Blesta.language")] : null;

		$this->pdf = new HtmlInvoicePdf("P", "px", $this->meta['paper_size'], true, 'UTF-8', false, $font);		
		$this->html = new HtmlInvoiceHtm("P", "px", $this->meta['paper_size'], true, 'UTF-8', false, $font);
		
		// Set the meta data to use for this invoice
		$this->pdf->meta = $this->meta;
		$this->html->meta = $this->meta;	
	}
	
	/**
	 * Sets whether the to address should be included in the invoice
	 */
	public function includeAddress($include_address=true) {	
	
		$this->pdf->include_address = (bool)$include_address;
		$this->html->include_address = (bool)$include_address;	
		
	}
	
	/**
	 * Sets the CurrencyFormat object for parsing currency values
	 *
	 * @param CurrencyFormat $currency_format The CurrencyFormat object
	 */
	public function setCurrency(CurrencyFormat $currency_format ) {
	
		$this->pdf->CurrencyFormat = $currency_format;
		$this->html->CurrencyFormat = $currency_format;		
		
	}
	
	/**
	 * Sets the Date object for parsing date values
	 *
	 * @param Date $date The Date object
	 */
	public function setDate(Date $date) {
	
		$this->pdf->Date = $date;
		$this->html->Date = $date;
		
	}
	
	/**
	 * Sets the MIME type to be used when fetching and streaming this invoice.
	 * Called after __construct()
	 *
	 * @param string $mime_type The mime_type to render ("application/pdf", "text/html", etc.)
	 */
	public function setMimeType($mime_type) {
		// print_r( basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
		if(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) == "pdf" ) 
			$this->mime_type = "application/pdf";
		else	
			$this->mime_type = $mime_type;
			
	}
	
	/**
	 * Returns all templates available to the HTML library
	 *
	 * @return array A list of template available
	 */
	private function getHtmlTemplate() {
		$dir = dirname(__FILE__) . DS . "template" . DS ;
		$template = array();
		$allowed_types = array("php");

		$dh  = opendir($dir);		
		while (false !== ($filename = readdir($dh))) {
			$name = substr($filename, 0, -4);
			$ext = substr($filename, strrpos($filename, '.') + 1);
			
			if(in_array($ext, array("php")))
				$template[$name] = $name;
		}

		asort($template);
		return $template;
	}
	
	/**
	 * Returns the MIME types that this template supports for output
	 */
	public function supportedMimeTypes() {
		
		$types = array();
		
		$types = $this->getHtmlTemplate() ;
		$types[] = "application/pdf";
		
		return $types ;
	}
	
	/**
	 * Returns the file extension for the given (supported) mime type
	 *
	 * @param string $mime_type The mime_type to fetch the extension of
	 * @return string The extension to use for the given mime type
	 */
	public function getFileExtension($mime_type) {
		switch ($mime_type) {
			case "application/pdf":
				return "pdf";
			default:
				return "html - ". $mime_type;
		}
		return null;
	}
	
	/**
	 * Returns the name of this invoice PDF template
	 */
	public function getName() {
		return Language::_("HtmlInvoice.name", true);
	}
 
	/**
	 * Returns the version of this invoice PDF template
	 *
	 * @return string The current version of this invoice PDF template
	 */
	public function getVersion() {
		return self::$version;
	}

	/**
	 * Returns the name and URL for the authors of this invoice PDF template
	 *
	 * @return array The name and URL of the authors of this invoice PDF template
	 */
	public function getAuthors() {
		return self::$authors;
	}
	
	/**
	 * Generates one or more invoices for a single document
	 *
	 * @param array $invoice_data An numerically indexed array of stdClass objects each representing an invoice
	 */
	public function makeDocument($invoice_data) {
	
		$num_invoices = count($invoice_data);

		// Loop through all of the given invoices
		for ($i=0; $i<$num_invoices; $i++) {
		
			// Set the invoice data for this invoice
			$this->invoice = $invoice_data[$i];
			
			// Set the invoice data for this PDF
			$this->pdf->invoice = $this->invoice;
			$this->html->invoice = $this->invoice;	
			
			// Start a new page group for each individual invoice
			$this->pdf->startPageGroup();
			
			// Add a new page so that each group starts on its own page
			$this->pdf->AddPage();

			// Draw all line items for this invoice
			$this->pdf->drawInvoice();
		}
		
	}
	
	/**
	 * Returns the invoice document in the desired format
	 *
	 * @return string The PDF document in binary format
	 */
	public function fetch() {
		
		switch ($this->mime_type) {
			case "application/pdf":
				return $this->pdf->Output(null, 'S');
		}
		
		return null;
	}
	
	/**
	 * Outputs the Invoice document to stdout, sending the apporpriate headers to render the document inline
	 *
	 * @param string $name The name for the document minus the extension (optional)
	 * @throws Exception Thrown when the MIME type is not supported by the template
	 */
	public function stream($name=null) {
		$name = $name === null ? $this->invoice->id_code : $name;
		
		switch ($this->mime_type) {
			case "application/pdf":
				$this->pdf->Output($name . "." . $this->getFileExtension($this->mime_type), 'I');
				exit;
		}
		throw new Exception("MIME Type: " . $this->mime_type . " not supported");
	}
	
	/**
	 * Outputs the Invoice document to stdout, sending the appropriate headers to force a download of the document
	 * 
	 * @param string $name The name for the document minus the extension (optional)
	 * @throws Exception Thrown when the MIME type is not supported by the template
	 */
	public function download($name=null) {
		// Use the Default Invoice PDF renderer for generating PDFs
		
		$name = $name === null ? $this->invoice->id_code : $name;
		
		switch ($this->mime_type) {
			case "application/pdf":
				$this->pdf->Output($name . "." . $this->getFileExtension($this->mime_type), 'D');
				exit;
				break;
			default:
				$this->html->Output(null , $this->mime_type );
				exit;
		}
		
		throw new Exception("MIME Type: " . $this->mime_type . " not supported");
	}

}
?>