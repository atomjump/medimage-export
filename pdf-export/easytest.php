<?php

 	require('easyTable.php');
 	$pdf = require('fpdf181/fpdf.php');
 	require('exfpdf.php');
 	
 	
 	 $pdf=new exFPDF();
 $pdf->AddPage(); 
 $pdf->SetFont('helvetica','',10);

 	
 $table=new easyTable($pdf, '%{70, 30}', 'align:L;');
 
 $table->easyCell('Peter: Says this', 'width:70%; align:L; bgcolor:#aaa; valign:T; img:images/test.jpg;'); //,w700,h1280
 $table->easyCell('21-Sep-2021  10:10am', 'width:30%; align:L; bgcolor:#aaa; valign:T;');
 //$table->easyCell('Text 2', 'bgcolor:#b3ccff; rowspan:2');
 //$table->easyCell('Text 3');
 $table->printRow();
 
 
  $table->easyCell('Fred: Says that', 'width:70%; align:L; bgcolor:#ccc;');
  $table->easyCell('21-Sep-2021  10:10am', 'align:L; bgcolor:#ccc;');
 $table->printRow();
 
 
   $table->easyCell('Text 3', 'align:L; bgcolor:#aaa;');
 $table->printRow();
 
 
 $table->easyCell('Fred:', 'align:L; bgcolor:#ccc; valign:T; img:images/test2.jpg;');
 $table->printRow();
 
    $table->easyCell('Text 5', 'align:L; bgcolor:#aaa;');
 $table->printRow();
 
 
 $table->easyCell('Peter:', 'align:L; bgcolor:#ccc; valign:T; img:images/test2.jpg;');
 $table->printRow();
 
 $table->easyCell('Text 6', 'align:L; bgcolor:#ccc; valign:T; img:images/test2.jpg;');
 $table->printRow();
 
 $table->easyCell('Text 7', 'align:L; bgcolor:#ccc; valign:T; img:images/test2.jpg;');
 $table->printRow();
 
 /*$table->rowStyle('min-height:20');
 $table->easyCell('Text 4', 'bgcolor:#3377ff; rowspan:3; img:test.jpg,h1280;');
 $table->printRow();

 $table->easyCell('Text 5', 'bgcolor:#99bbff; rowspan:3;');
 $table->printRow();*/
 
 $table->endTable();
 
 $pdf->Output();
 
?>