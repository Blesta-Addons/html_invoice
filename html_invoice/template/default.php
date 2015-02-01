<?php
class TemplateFile {
	
	private $content;
	
	public function FinalTemplate
		(	
			$Header=null , $HtmlDir=null ,  $HtmlTitle=null ,  $RtlCss=null ,  $drawBackground=null , 
			$drawLogo=null , $drawPaidWatermark=null , $drawInvoiceType=null , $drawInvoiceInfo=null , $drawReturnAddress=null ,  $drawAddress=null , 
			$drawLineHeader=null , $drawInvoice=null , $SubTotals=null , $Taxes=null , $Totals=null , $PublicNotes=null , $drawPayments=null , 
			$drawTerms=null , $Footer=null , $PrintBtn=null , $DownloadBtn=null 
		) 
	{
		/*
		*
		NOTE , The $Header include all the header 
		*
		*/
		$paid_watermark = '
				<button type="button" class="btn btn-success btn-lg active" >
					<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> '. Language::_("HtmlInvoice.watermark_paid", true).'
				</button>';
		$unpaid_watermark = '
				<button type="button" class="btn btn-danger btn-lg active" >
					<span class="glyphicon glyphicon-remove" aria-hidden="true"></span> '. Language::_("HtmlInvoice.watermark_unpaid", true).'
				</button>';
		$download_btn = '
				<button type="button" class="btn btn-info btn-lg " >
					<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> '. Language::_("HtmlInvoice.download_invoice", true).'
				</button>';
		$print_btn = '
				<button type="button" class="btn btn-info btn-lg" onclick="javascript:window.print();" >
				  <span class="glyphicon glyphicon-print" aria-hidden="true"></span> '. Language::_("HtmlInvoice.print_invoice", true).'
				</button>';
				
		$content = '
		<!doctype html>
		<html dir="'. $HtmlDir .'">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<meta name="viewport" content="width=device-width, initial-scale=1.0">	
				<title>'. $HtmlTitle .' </title>				
				<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
				'. (Language::_("AppController.lang.dir", true) == "rtl" ? '<link rel="stylesheet" href="//cdn.rawgit.com/morteza/bootstrap-rtl/master/dist/cdnjs/3.3.1/css/bootstrap-rtl.min.css">' : '' )  .'
				<style>
					'. $drawBackground .'
					'. $RtlCss .'
					header {margin-top: 70px;}
				</style>				
			</head>
			<body>
				<div class="container">				
					<header class="header">
						<div class="row">
							<div class="col-xs-6">
								'. $drawLogo .'								
							</div>
							<div class="col-xs-6 text-right flip ">
								<h1>'. $drawInvoiceType .'</h1>
								'. nl2br($drawInvoiceInfo) .'
							</div>
						</div>
						<div class="row">
							<div class="col-xs-5">
								'. $drawReturnAddress .' 
							</div>
							<div class="col-xs-5 col-xs-offset-2 text-right">
								'. $drawAddress .'
							</div>
						</div>
						<!-- / end client details section -->
						<div class="row">
								<div class="col-xs-12  text-right">								
									'. ($drawPaidWatermark ? $paid_watermark : $unpaid_watermark ) .'
									'. ($PrintBtn ? $print_btn : "" ) .'
									'. ($DownloadBtn ? $download_btn : "" ) .'
									
								</div>
						</div>
						
					</header>
					<div class="content">

						
						<table class="table table-hover table-bordered">
							<thead>
								<tr>'. $drawLineHeader .'</tr>
							</thead>
							<tbody>
								'. $drawInvoice .'
							</tbody>
						</table>
						
						<div class="row ">
							<div class="col-xs-2 col-xs-offset-6">
								
							</div>
							<div class="col-xs-4 text-right">
								<p>
									<strong>
										'. $SubTotals .'
										'. $Taxes .'
										'. $Totals .'
									</strong>
								</p>
							</div>
						</div>
						
						<div class="row">
							<div class="col-xs-5">
								'. $PublicNotes .'
							</div>
							<div class="col-xs-7">
								'. $drawPayments .'	
							</div>
						</div>
						
					</div>
					<footer class="footer">		
						'. $drawTerms .'
						'. $Footer .'
					</footer>	
				</div>
			</body>
		</html>';
		
		return $content ;
	}
}
?>