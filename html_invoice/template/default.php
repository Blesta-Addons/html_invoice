<?php
class TemplateFile {
	
	private $content;
	
	public function FinalTemplate
		(	
			$Header=null , $HtmlDir=null ,  $HtmlTitle=null ,  $RtlCss=null ,  $drawBackground=null , 
			$drawLogo=null , $drawPaidWatermark=null , $drawInvoiceType=null , $drawInvoiceInfo=null , $drawReturnAddress=null ,  $drawAddress=null , 
			$drawLineHeader=null , $drawInvoice=null , $SubTotals=null , $Taxes=null , $Totals=null , $PublicNotes=null , $drawPayments=null , 
			$drawTerms=null , $Footer=null 
		) 
	{
		/*
		*
		NOTE , The $Header include all the header 
		*
		*/
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
								<div class="col-xs-3 col-xs-offset-9  text-right">								
									'. ($drawPaidWatermark ? "<h1><span class='label label-success'>". $drawPaidWatermark ."</span></h1>" : "<h1><span class='label label-danger'>". Language::_("HtmlInvoice.watermark_unpaid", true) ."</span></h1>" ) .'
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