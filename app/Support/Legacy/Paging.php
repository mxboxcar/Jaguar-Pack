<?php

namespace App\Support\Legacy;

/**
 * getpaging() ของ system_paging.php เดิม (ใช้ใน grid ajax 51 ไฟล์) — คัดลอกตรงตัว ผล HTML เหมือนเดิมทุกตัวอักษร
 * $ajaxpage = path ajax ของ grid (เช่น '_tracking/grid') ถูกฝังใน onclick → ต้องเป็นค่าจากโค้ดเท่านั้น ห้ามมาจาก input
 */
final class Paging
{
    public static function getpaging(mixed $page, mixed $totalitems, mixed $limit = 15, string $ajaxpage = ''): string
    {
        $page = (int) $page;
        $totalitems = (int) $totalitems;
        $limit = (int) $limit;
    		
    	//defaults
    	$adjacents = 1;
    	if(!$limit) $limit = 15;
    	if(!$page) $page = 1;
    	
    	
    	//other vars
    	$prev = $page - 1;									//previous page is page - 1
    	$next = $page + 1;									//next page is page + 1
    	$lastpage = ceil($totalitems / $limit);				//lastpage is = total items / items per page, rounded up.
    	$lpm1 = $lastpage - 1;								//last page minus 1
    	
    	/* 
    		Now we apply our rules and draw the pagination object. 
    		We're actually saving the code to a variable in case we want to draw it more than once.
    	*/
    	$pagination = "";
    	if($lastpage > 1)
    	{	
    		$pagination .= "<ul class=\"pagination form-group\" style=\"padding:0px;margin:0px;\">";
    
    		//previous button
    		if ($page > 1) 
    			$pagination .= "<li class=\"\" onclick=\"Javascript:loadAjaxGrid('".$ajaxpage."', '".$prev."',  $('#txtSearch').val());\"><a href=\"javascript:void(0)\">&laquo;</a></li>";
    		else
    			$pagination .= "<li class=\"disabled\"><a href=\"javascript:void(0)\">&laquo;</a></li>";	
    		
    		//pages	
    		if ($lastpage < 7 + ($adjacents * 2))	//not enough pages to bother breaking it up
    		{	
    			for ($counter = 1; $counter <= $lastpage; $counter++)
    			{
    				if ($counter == $page)
    					$pagination .= "<li class=\"active\"><a href=\"javascript:void(0)\">".$counter."</a></li>";
    				else
    					$pagination .= "<li class=\"\" onclick=\"Javascript:loadAjaxGrid('".$ajaxpage."', '".$counter."',  $('#txtSearch').val());\" ><a href=\"javascript:void(0)\">".$counter."</a></li>";					
    			}
    		}
    		elseif($lastpage >= 7 + ($adjacents * 2))	//enough pages to hide some
    		{
    			//close to beginning; only hide later pages
    			if($page < 1 + ($adjacents * 3))		
    			{
    				for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
    				{
    					if ($counter == $page)
    						$pagination .= "<li class=\"active\"><a href=\"javascript:void(0)\">".$counter."</a></li>";
    					else
    						$pagination .= "<li class=\"\" onclick=\"Javascript:loadAjaxGrid('".$ajaxpage."', '".$counter."',  $('#txtSearch').val());\" ><a href=\"javascript:void(0)\">".$counter."</a></li>";					
    				}
    				$pagination .= "<li class=\"\" ><a href=\"javascript:void(0)\">...</a></li>";
    				$pagination .= "<li class=\"\" onclick=\"Javascript:loadAjaxGrid('".$ajaxpage."', '".$lpm1."',  $('#txtSearch').val());\" ><a href=\"javascript:void(0)\">$lpm1</a></li>";
    				$pagination .= "<li class=\"\" onclick=\"Javascript:loadAjaxGrid('".$ajaxpage."', '".$lastpage."',   $('#txtSearch').val());\" ><a href=\"javascript:void(0)\">$lastpage</a></li>";
    			}
    			//in middle; hide some front and some back
    			elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
    			{
    				$pagination .= "<li class=\"\" onclick=\"Javascript:loadAjaxGrid('".$ajaxpage."', '1',   $('#txtSearch').val());\" ><a href=\"javascript:void(0)\">1</a></li>";
    				$pagination .= "<li class=\"\" onclick=\"Javascript:loadAjaxGrid('".$ajaxpage."', '2',   $('#txtSearch').val());\" ><a href=\"javascript:void(0)\">2</a></li>";
    				$pagination .= "<li class=\"\" ><a href=\"javascript:void(0)\">...</a></li>";
    				for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
    				{
    					if ($counter == $page)
    						$pagination .= "<li class=\"active\"><a href=\"javascript:void(0)\">".$counter."</a></li>";
    					else
    						$pagination .= "<li class=\"\" onclick=\"Javascript:loadAjaxGrid('".$ajaxpage."', '".$counter."',  $('#txtSearch').val());\" ><a href=\"javascript:void(0)\">".$counter."</a></li>";					
    				}
    				$pagination .= "<li class=\"\" ><a href=\"javascript:void(0)\">...</a></li>";
    				$pagination .= "<li class=\"\" onclick=\"Javascript:loadAjaxGrid('".$ajaxpage."', '".$lpm1."',  $('#txtSearch').val());\" ><a href=\"javascript:void(0)\">$lpm1</a></li>";
    				$pagination .= "<li class=\"\" onclick=\"Javascript:loadAjaxGrid('".$ajaxpage."', '".$lastpage."',   $('#txtSearch').val());\" ><a href=\"javascript:void(0)\">$lastpage</a></li>";	
    			}
    			//close to end; only hide early pages
    			else
    			{
    				$pagination .= "<li class=\"\" onclick=\"Javascript:loadAjaxGrid('".$ajaxpage."', '1',   $('#txtSearch').val());\" ><a href=\"javascript:void(0)\">1</a></li>";
    				$pagination .= "<li class=\"\" onclick=\"Javascript:loadAjaxGrid('".$ajaxpage."', '2',   $('#txtSearch').val());\" ><a href=\"javascript:void(0)\">2</a></li>";
    				$pagination .= "<li class=\"\" ><a href=\"javascript:void(0)\">...</a></li>";
    				for ($counter = $lastpage - (1 + ($adjacents * 3)); $counter <= $lastpage; $counter++)
    				{
    					if ($counter == $page)
    						$pagination .= "<li class=\"active\"><a href=\"javascript:void(0)\">".$counter."</a></li>";
    					else
    						$pagination .= "<li class=\"\" onclick=\"Javascript:loadAjaxGrid('".$ajaxpage."', '".$counter."',   $('#txtSearch').val());\" ><a href=\"javascript:void(0)\">".$counter."</a></li>";				
    				}
    			}
    		}
    		
    		//next button
    		if ($page < $counter - 1) 
    			$pagination .= "<li class=\"\" onclick=\"Javascript:loadAjaxGrid('".$ajaxpage."', '".$next."',  $('#txtSearch').val());\"><a href=\"javascript:void(0)\">&raquo;</a></li>";
    		else
    			$pagination .= "<li class=\"disabled\"><a href=\"javascript:void(0)\">&raquo;</a></li>";
    		$pagination .= "</ul>\n";
    	}
    	
    	return $pagination;
    
    }
}
