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
// Uses the TcpdfWrapper for rendering the PDF
Loader::load(COMPONENTDIR . "invoice_templates" . DS . "tcpdf_wrapper.php");

class HtmlInvoicePdf extends TcpdfWrapper {
	/**
	 * @var string Holds the default font size for this document
	 */
	private static $font_size = 9;
	/**
	 * @var string Holds the alternate font size for this document
	 */
	private static $font_size_alt = 7;
	/**
	 * @var string Holds the second alternate font size for this document
	 */
	private static $font_size_alt2 = 10;
	/**
	 * @var string Holds the third alternate font size for this document
	 */
	private static $font_size_alt3 = 20;
	/**
	 * @var string The primary font family to use
	 */
	private $font = "dejavusanscondensed";
	/**
	 * @var array An RGB representation of the primary color used throughout
	 */
	private static $primary_color = array(175, 176, 177);
	/**
	 * @var array An RGB representation of the primary text color used throughout
	 */
	private static $primary_text_color = array(83, 84, 86);
	/**
	 * @var array The standard number format options
	 */
	private static $standard_num_options = array('prefix'=>false,'suffix'=>false,'code'=>false,'with_separator'=>false);
	/**
	 * @var array An array of meta data for this invoice
	 */
	public $meta = array();
	/**
	 * @var CurrencyFormat The CurrencyFormat object used to format currency values
	 */
	public $CurrencyFormat;
	/**
	 * @var Date The Date object used to format date values
	 */
	public $Date;
	/**
	 * @var array An array of invoice data for this invoice
	 */
	public $invoice = array();
	/**
	 * @var int The Y position where the header finishes its content
	 */
	private $header_end_y = 0;
	/**
	 * @var array An array of line item options
	 */
	private $line_options = array();
	/**
	 * @var array An array of transaction payment row options
	 */
	private $payment_options = array();
	/**
	 * @var int The y_pos to start the table headings at
	 */
	private $table_heading_y_pos = 233;
	/**
	 * @param boolean Whether to include the to address or not
	 */
	public $include_address = true;
	
	
	public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $font=null) {
		
		Language::loadLang("pdf_invoice", null, dirname(__FILE__) . DS . "language" . DS);
		
		// Invoke the parent constructor
		parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);
		
		$this->line_options = array(
			'font_size'=>self::$font_size,
			'x_pos'=>44,
			'y_pos'=>$this->table_heading_y_pos,
			'border'=>"BR",
			'height'=>22,
			'line_style'=>array('width'=>0.5,'cap'=>"butt",'join'=>"miter",'dash'=>2,'color'=>self::$primary_color),
			'font_size'=>self::$font_size_alt,
			'padding'=>self::$font_size_alt,
			'col'=> array(
				'name'=> array(
					'width'=>312
				),
				'qty'=> array(
					'width'=>70,
					'align'=>'C'
				),
				'unit_price'=> array(
					'width'=>70,
					'align'=>'R'
				),				
				'price'=> array(
					'width'=>70,
					'align'=>'R',
					'border'=>"B"
				),
			),
			'cell'=>array(array('name'=>array('align'=>'L')))
		);
		
		$this->payment_options = array(
			'x_pos'=>44,
			'y_pos'=>$this->table_heading_y_pos,
			'border'=>"BR",
			'height'=>22,
			'line_style'=>array('width'=>0.5,'cap'=>"butt",'join'=>"miter",'dash'=>2,'color'=>self::$primary_color),
			'font_size'=>self::$font_size_alt,
			'padding'=>self::$font_size_alt,
			'col'=> array(
				'applied_date'=> array(
					'width'=>130.5
				),
				'type_name'=> array(
					'width'=>130.5,
					'align'=>'C'
				),
				'transaction_id'=> array(
					'width'=>130.5,
					'align'=>'R'
				),				
				'applied_amount'=> array(
					'width'=>130.5,
					'align'=>'R',
					'border'=>"B"
				),
			),
			'cell'=>array(array('applied_date'=>array('align'=>'L')))
		);
		
		// Set tag to use on page numbering replacement
		$this->AliasNbPages("{P}");

		// Set image scale factor
		$this->setImageScale(2); 
		
		// Default monospaced font
		$this->SetDefaultMonospacedFont("courier");

		$this->setFontInfo($font);
		
		// Set margins
		$this->SetMargins(25, 260, 25);
		$this->SetFooterMargin(160);
		
		// Set auto page breaks y-px from the bottom of the page
		$this->SetAutoPageBreak(true, 190);
	}
	
	/**
	 * Overwrite the default header that appears on each page of the PDF
	 */
	public function Header() {
		
		// Draw the background
		$this->drawBackground();
		
		// Draw the paid text in the background
		$this->drawPaidWatermark();
		
		// Set logo
		$this->drawLogo();
		
		// Set the page mark so background images will display correctly
		$this->setPageMark();

		// Draw the return address
		$this->drawReturnAddress();
		
		// Place the invoice type on the document
		$this->drawInvoiceType();

		// Set Address
		if ($this->include_address)
			$this->drawAddress();
		
		// Draw Tax ID
		$this->drawTaxId();

		// Add Invoice Number, Customer Number, Invoice Date, Invoice Due Date
		$this->drawInvoiceInfo();
		
		// Draw the line items table heading on each page
		$this->drawLineHeader();
		
		// Set the position where the header finishes
		$this->header_end_y = $this->GetY();
		
		// Set the top margin again, incase any header methods expanded this area.
		$this->SetTopMargin($this->header_end_y);
	}
	
	/**
	 * Overwrite the default footer that appears on each page of the PDF
	 */
	public function Footer() {

		// Set the terms of the document
		if (!empty($this->meta['terms']))
			$this->drawTerms();
		
		// Set the page number of the document
		$this->drawPageNumber();
	}
	
	/**
	 * Draws a complete invoice
	 */
	public function drawInvoice() {
		
		$options = $this->line_options;
		$options['y_pos'] = max($this->header_end_y, $this->GetY());
		
		// Build line items
		$lines = array();

		for ($i=0; $i<count($this->invoice->line_items); $i++) {
			$lines[] = array(
				'name'=>$this->invoice->line_items[$i]->description,
				'qty'=>$this->CurrencyFormat->truncateDecimal($this->invoice->line_items[$i]->qty, 0),
				'unit_price'=>$this->CurrencyFormat->format($this->invoice->line_items[$i]->amount, $this->invoice->currency, self::$standard_num_options),
				'price'=>$this->CurrencyFormat->format($this->invoice->line_items[$i]->total, $this->invoice->currency, self::$standard_num_options),
			);
		}
		
		// Draw invoice lines
		$this->drawTable($lines, $options);
		
		// Draw public notes and invoice tallies
		$this->drawTallies();
		
		// Draw transaction payments/credits
		$this->drawPayments();
	}
	
	/**
	 * Set the fonts and font attributes to be used in the document
	 */
	private function setFontInfo($font) {
		$lang = array();
		$lang['a_meta_charset'] = 'UTF-8';
		$lang['a_meta_dir'] = 'ltr' ;
		
		// Set language settings
		$this->setLanguageArray($lang);
		
		if ($font)
			$this->font = $font;
		
		// Set font
		$this->SetFont($this->font, '', self::$font_size);
		
		// Set default text color
		$this->SetTextColorArray(self::$primary_text_color);
	}
	
	/**
	 * Draws the paid text in the background of the invoice
	 */
	private function drawPaidWatermark() {
		// Show paid watermark
		if (!empty($this->meta['display_paid_watermark']) && $this->meta['display_paid_watermark'] == "true" && ($this->invoice->date_closed != null)) {
			$max_height = $this->getPageHeight();
			$max_width = $this->getPageWidth();
			
			$options = array(
				'x_pos'=>25, // start within margin
				'y_pos'=>($max_height - 125)/2, // vertical center
				'font_size'=>100,
				'row'=>array(array('font_style' => "B", 'align'=>"C"))
			);
			
			$data = array(
				array('col'=>Language::_("PdfInvoice.watermark_paid", true))
			);
			
			// Set paid background text color
			$this->SetTextColorArray(array(230,230,230));
			
			// Rotate the text
			$this->StartTransform();
			// Rotate 45 degrees from midpoint
			$this->Rotate(45, ($max_width)/2, ($max_height)/2);
			
			$this->drawTable($data, $options);
			
			$this->StopTransform();
			
			// Set default text color
			$this->SetTextColorArray(self::$primary_text_color);
		}
	}
	
	/**
	 * Renders public notes and invoice tallies onto the document
	 */
	private function drawTallies() {
		
		$page = $this->getPage();
		
		$options = array(
			'border'=>0,
			'x_pos'=>44,
			'y_pos'=>max($this->header_end_y, $this->GetY()),
			'font_size'=>self::$font_size_alt,
			'col'=>array(
				array(
					'height'=>12,
					'width'=>382
				)
			),
			'row'=>array(array('font_style' => "B"))
		);

		
		// Draw notes
		$y_pos = 0;
		if (!empty($this->invoice->note_public)) {
			$note_options = $options;
			$note_options['y_pos'] += 4.5;
			
			$data = array(
				array(Language::_("PdfInvoice.notes_heading", true)),
				array($this->invoice->note_public)
			);
			// Draw notes
			$this->drawTable($data, $note_options);
			$y_pos = $this->GetY();
		}
		
		$this->setPage($page);
		
		// Set subtitle
		$data = array(
			array('notes'=>null,'label'=>Language::_("PdfInvoice.subtotal_heading", true),'price'=>$this->CurrencyFormat->format($this->invoice->subtotal, $this->invoice->currency, self::$standard_num_options))
		);
		// Set all taxes
		foreach ($this->invoice->taxes as $tax) {
			$data[] = array('notes'=>null,'label'=>Language::_("PdfInvoice.tax_heading", true, $tax->name, $tax->amount),'price'=>$this->CurrencyFormat->format($tax->tax_total, $this->invoice->currency, self::$standard_num_options));
		}
		// Set total
		$data[] = array('notes'=>null,'label'=>Language::_("PdfInvoice.total_heading", true),'price'=>$this->CurrencyFormat->format($this->invoice->total, $this->invoice->currency));

		
		$options['row'] = array('label'=>array('border'=>'R'), 'price'=>array('border'=>0));
		$options['padding'] = self::$font_size_alt;
		$options['col'] = array(
			'notes'=> array(
				'width'=>382
			),
			'label'=> array(
				'width'=>70,
				'align'=>'R',
				'border'=>"TR",
				'font_style' => "B"
			),				
			'price'=> array(
				'width'=>70,
				'align'=>'R',
				'border'=>"T"
			),
		);
		
		// Draw tallies
		$this->drawTable($data, $options);
		
		// Set the Y position to the greater of the notes area, or the subtotal/total area
		$this->SetY(max($y_pos, $this->GetY()));
	}
	
	/**
	 * Renders a heading, typically above a table
	 */
	private function drawTableHeading($heading) {
		$options = array(
			'x_pos'=>44,
			'y_pos'=>max($this->table_heading_y_pos, $this->GetY()),
			'col'=> array(
				'heading'=> array(
					'align' => 'L',
					'font_style' => "B",
					'border' => 0
				)
			),
			'font_size'=>self::$font_size_alt2,
			'padding'=>0,
		);
		
		$data = array(
			array('heading' => $heading),
		);
		
		// Draw table heading
		$this->drawTable($data, $options);
	}
	
	/**
	 * Renders the transaction payments/credits header onto the document
	 */
	private function drawPaymentHeader() {
		// Add a heading above the table
		$this->drawTableHeading(Language::_("PdfInvoice.payments_heading", true));
		
		// Use the same options as line items
		$options = $this->payment_options;
		// Start header at top of page, or current page below the other table
		$options['y_pos'] = max($this->table_heading_y_pos, $this->GetY());
		
		// Draw the transaction payment header
		$options['row'] = array(array('font_size'=>self::$font_size_alt2,'fill_color'=>self::$primary_text_color, 'border'=>"0",'align'=>'C','text_color'=>array(255,255,255)));
		
		$header = array(array(
			'applied_date' => Language::_("PdfInvoice.payments_applied_date", true),
			'type_name' => Language::_("PdfInvoice.payments_type_name", true),
			'transaction_id' => Language::_("PdfInvoice.payments_transaction_id", true),
			'applied_amount' => Language::_("PdfInvoice.payments_applied_amount", true)
		));
		
		$this->drawTable($header, $options);
	}
	
	/**
	 * Renders the transaction payments/credits section onto the document
	 */
	private function drawPayments() {
		if (!empty($this->meta['display_payments']) && $this->meta['display_payments'] == "true") {
			// Set the payment rows
			$rows = array();
			for ($i=0; $i<count($this->invoice->applied_transactions); $i++) {
				// Only show approved transactions
				if ($this->invoice->applied_transactions[$i]->status != "approved")
					continue;
				
				$rows[] = array(
					'applied_date' => $this->Date->cast($this->invoice->applied_transactions[$i]->applied_date, $this->invoice->client->settings['date_format']),
					'type_name' => $this->invoice->applied_transactions[$i]->type_real_name,
					'transaction_id' =>$this->invoice->applied_transactions[$i]->transaction_id,
					'applied_amount' => $this->CurrencyFormat->format($this->invoice->applied_transactions[$i]->applied_amount, $this->invoice->applied_transactions[$i]->currency, self::$standard_num_options)
				);
			}
			
			// Don't draw the table if there are no payments
			if (empty($rows))
				return;
			
			// Draw the table heading
			$this->drawPaymentHeader();
			
			// Draw the table rows
			$options = $this->payment_options;
			$options['y_pos'] = max($this->table_heading_y_pos, $this->header_end_y, $this->GetY());
			$this->drawTable($rows, $options);
			
			// Set balance due at bottom of table
			$data = array(array('blank'=>"",'label'=>Language::_("PdfInvoice.balance_heading", true),'price'=>$this->CurrencyFormat->format($this->invoice->due, $this->invoice->currency)));
			
			$options['y_pos'] = max($this->table_heading_y_pos, $this->header_end_y, $this->GetY());
			$options['row'] = array('blank'=>array('border'=>0));
			$options['padding'] = self::$font_size_alt;
			$options['col'] = array(
				'blank'=> array(
					'width'=>261,
					'border'=>0
				),
				'label'=> array(
					'width'=>130.5,
					'align'=>'R',
					'border'=>"R",
					'font_style' => "B"
				),				
				'price'=> array(
					'width'=>130.5,
					'align'=>'R',
					'border'=>0
				),
			);
			
			// Draw balance
			$this->drawTable($data, $options);
		}
	}
	
	/**
	 * Renders the background image onto the document
	 */
	private function drawBackground() {
		
        // Set background image by fetching current margin break,
		// then disable page break, set the image, and re-enable page break with
		// the current margin
        $bMargin = $this->getBreakMargin();
        $auto_page_break = $this->AutoPageBreak;
        $this->SetAutoPageBreak(false, 0);
		if (file_exists($this->meta['background']))
			$this->Image($this->meta['background'], 0, 0, 0, 0, '', '', '', false, 300, '', false, false, 0);
			
        // Restore auto-page-break status
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
	}
	
	/**
	 * Renders the logo onto the document
	 */
	private function drawLogo() {
		
		// Really wish we could align right, but aligning right will not respect the
		// $x parameter in TCPDF::Image(), so we must manually set the off-set. That's ok
		// because we're setting the width anyway.
		if ($this->meta['display_logo'] == "true" && file_exists($this->meta['logo']))
			$this->Image($this->meta['logo'], 432, 35, 140);
	}
	
	/**
	 * Renders the tax ID section to the document
	 */
	private function drawTaxId() {
		$data = array();
		$options = array(
			'font_size'=>self::$font_size,
			'x_pos'=>185,
			'y_pos'=>130,
			'col'=> array(
				'name'=> array(
					'width'=>285,
					'align'=>'R'
				),
				'space'=>array(
					'width'=>10
				),
				'value'=> array(
					'width'=>85,
					'align'=>'L'
				)
			)
		);
		
		if (isset($this->meta['tax_id']) && $this->meta['tax_id'] != "") {
			$data[] = array(
				'name'=>Language::_("PdfInvoice.tax_id", true),
				'space'=>null,
				'value'=>$this->meta['tax_id']
			);
		}
		if (isset($this->invoice->client->settings['tax_id']) && $this->invoice->client->settings['tax_id'] != "") {
			$data[] = array(
				'name'=>Language::_("PdfInvoice.client_tax_id", true),
				'space'=>null,
				'value'=>$this->invoice->client->settings['tax_id']
			);
		}

		// Draw Tax/VAT ID
		$this->drawTable($data, $options);
	}
	
	/**
	 * Renders the Invoice info section to the document, containing the invoice ID, client ID, date billed, and date due
	 */
	private function drawInvoiceInfo() {
		$data = array(
			array(
				'name'=>Language::_("PdfInvoice.type_" . $this->invoice->status, true) . Language::_("PdfInvoice.invoice_id_code", true),
				'space'=>null,
				'value'=>$this->invoice->id_code
			),
			array(
				'name'=>Language::_("PdfInvoice.client_id_code", true),
				'space'=>null,
				'value'=>$this->invoice->client->id_code
			),
			array(
				'name'=>Language::_("PdfInvoice.date_billed", true),
				'space'=>null,
				'value'=>$this->Date->cast($this->invoice->date_billed, $this->invoice->client->settings['date_format'])
			),
			array(
				'name'=>Language::_("PdfInvoice.date_due", true),
				'space'=>null,
				'value'=>$this->Date->cast($this->invoice->date_due, $this->invoice->client->settings['date_format'])
			)
		);
		
		$options = array(
			'font_size'=>self::$font_size,
			'x_pos'=>185,
			'y_pos'=>max(158, $this->GetY()-$this->header_end_y),
			'col'=> array(
				'name'=> array(
					'width'=>285,
					'align'=>'R'
				),
				'space'=>array(
					'width'=>10
				),
				'value'=> array(
					'width'=>85,
					'align'=>'L'
				)
			)
		);
		$this->drawTable($data, $options);
	}
	
	/**
	 * Renders the line items table heading
	 */
	private function drawLineHeader() {
		$options = $this->line_options;
		$options['row']= array(array('font_size'=>self::$font_size_alt2,'fill_color'=>self::$primary_text_color, 'border'=>"0",'align'=>'C','text_color'=>array(255,255,255)));
		
		$header = array(array(
			'name'=>Language::_("PdfInvoice.lines_description", true),
			'qty'=>Language::_("PdfInvoice.lines_quantity", true),
			'unit_price'=>Language::_("PdfInvoice.lines_unit_price", true),
			'price'=>Language::_("PdfInvoice.lines_cost", true)
		));
		
		$this->drawTable($header, $options);
	}
	
	/**
	 * Renders the to address information
	 */
	private function drawAddress() {
		$address = $this->invoice->billing->first_name . " " . $this->invoice->billing->last_name . "\n";
		if (strlen($this->invoice->billing->company) > 0)
			$address .= $this->invoice->billing->company . "\n";
		$address .= $this->invoice->billing->address1 . "\n";
		if (strlen($this->invoice->billing->address2) > 0)
			$address .= $this->invoice->billing->address2 . "\n";
		$address .= $this->invoice->billing->city . ", " . $this->invoice->billing->state . " " . $this->invoice->billing->zip . " " . $this->invoice->billing->country->alpha3;
		
		$data = array(
			array($address)
		);
		$options = array(
			'font_size'=>self::$font_size,
			'x_pos'=>44,
			'y_pos'=>157,
			'col'=> array(
				array('width'=>210)
			)
		);
		$this->drawTable($data, $options);
	}
	
	/**
	 * Renders the return address information
	 */
	private function drawReturnAddress() {
		if ($this->meta['display_companyinfo'] == "false")
			return;
		$data = array(
			array($this->meta['company_name']),
			array($this->meta['company_address'])
		);
		$options = array(
			'font_size'=>self::$font_size,
			'y_pos'=>38,
			'x_pos'=>44,
			'align'=>'L'
		);
		$this->drawTable($data, $options);
	}
	
	/**
	 * Sets the invoice type on the document based upon the status of the invoice
	 */
	private function drawInvoiceType() {
		$data = array(
			array(Language::_("PdfInvoice.type_" . $this->invoice->status, true))
		);
		$options = array(
			'font_size'=>self::$font_size_alt3,
			'font_style' => "B",
			'y_pos'=>114,
			'x_pos'=>43,
			'align'=>'L'
		);
		$this->drawTable($data, $options);
	}
	
	/**
	 * Renders the page number to the document
	 */
	private function drawPageNumber() {
		$data = array(
			array(Language::_("PdfInvoice.page_of", true, $this->getGroupPageNo(), $this->getPageGroupAlias()))
		);
		$options = array(
			'font_size'=>self::$font_size_alt,
			'font_style' => "B",
			'y_pos'=>-52,
			'align'=>'R'
		);
		$this->drawTable($data, $options);
	}
	
	/**
	 * Renders the terms of this document
	 */
	private function drawTerms() {
		$data = array(
			array(Language::_("PdfInvoice.terms_heading", true)),
			array($this->meta['terms'])
		);
		$options = array(
			'font_size'=>self::$font_size_alt,
			'border'=>0,
			'x_pos'=>48,
			'y_pos'=>-119,
			'col'=>array(array('height'=>12)),
			'row'=>array(array('font_style' => "B"))
		);
		$this->drawTable($data, $options);
	}
}
?>