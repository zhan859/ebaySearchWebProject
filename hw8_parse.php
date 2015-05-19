<?php 
    	$url = "http://svcs.eBay.com/services/search/FindingService/v1?siteid=0&SECURITY-APPNAME=xiweiliu-c66b-45c6-bfc2-dd3e100fbfcf&OPERATION-NAME=findItemsAdvanced&SERVICE-VERSION=1.0.0&RESPONSE-DATA-FORMAT=XML";
    	$url = $url."&keywords=".urlencode($_GET["keywords"]);
   		$url = $url."&sortOrder=".urlencode($_GET["sort"]);
   		$url = $url."&paginationInput.entriesPerPage=".urlencode($_GET["perPage"]); 
   		$url = $url."&paginationInput.pageNumber=".urlencode($_GET["curPageNum"]);
   		$counter = 0;   	
    	if($_GET["pricefrom"] != "") {
    		$url = $url."&itemFilter($counter).name=MinPrice&itemFilter($counter).value=".$_GET["pricefrom"];
   			$counter++;
   		}
   		if($_GET["priceto"] != "") {
   			$url = $url."&itemFilter($counter).name=MaxPrice&itemFilter($counter).value=".$_GET["priceto"];
    		$counter++;    			
    	}
   		if(!empty($_GET["condition"])) {
   			$url = $url."&itemFilter($counter).name=Condition";
   			$i = 0;
   			foreach($_GET["condition"] as $cval){
    			$url = $url."&itemFilter($counter).value($i)=$cval";
    			$i++;
    		}
    		$counter++;
   		}
   		if(!empty($_GET["buyformates"])) {
   			$url = $url."&itemFilter($counter).name=ListingType";
   			$i = 0;
    		foreach($_GET['buyformates'] as $bval) {
    			$url = $url."&itemFilter($counter).value($i)=$bval";
    			$i++;
   			}
   			$counter++;
   		}
   		if(isset($_GET["ReturnsAcceptedOnly"])) {
    		$url = $url."&itemFilter($counter).name=ReturnsAcceptedOnly&itemFilter($counter).value=true";
    		$counter++;
    	}
   		if(isset($_GET['freeship'])) {
   			$url = $url."&itemFilter($counter).name=FreeShippingOnly&itemFilter($counter).value=true";
   			$counter++;
   		}
    	if(isset($_GET['expeditedship'])) {
    		$url = $url."&itemFilter($counter).name=ExpeditedShippingType&itemFilter($counter).value=Expedited";
   			$counter++;
   		}
   		if($_GET['MaxHandlingTime'] != "") {
   			$url = $url."&itemFilter($counter).name=MaxHandlingTime&itemFilter($counter).value=".$_GET['MaxHandlingTime'];
    		$counter++;
    	}
    	$url = $url."&outputSelector[0]=SellerInfo&outputSelector[1]=PictureURLSuperSize&outputSelector[2]=StoreInfo";
//    	echo "<p>".$url."</p>";

    	$xml = simplexml_load_file($url) or die("Error: Cannot create object");
   		if($xml == null) {
   			$result = array("ack" => "oops");
   			echo json_encode($result);
   		} else if($xml->ack == "Success"){
    		if($xml->paginationOutput->totalEntries == 0) {
    			$result = array("ack" => "No results found");
    			echo json_encode($result);  // echo "No results found";
    		} else {
    			XML2JSON($xml);
    		}
    	} else {
    		$error = array("ack" => "No results found");
    		echo json_encode($error);  //	echo "something wrong";
    	}
    	
    	function XML2JSON($xml){
    		$result = array("ack" => (string)$xml->ack, "resultCount" => (string)$xml->paginationOutput->totalEntries, "pageNumber" => (string)$xml->paginationOutput->pageNumber, "itemCount" => (string)$xml->paginationOutput->entriesPerPage);
    		$itemNum = (int)$xml->searchResult[0]['count'];
    		for($i = 0; $i < $itemNum; $i++) {
    			$basicInfo = basic($xml, $i);
    			$sellerInfo= seller($xml, $i);
    			$shippingInfo= shipping($xml, $i);
    			$info = array("basicInfo" => $basicInfo, "sellerInfo" => $sellerInfo, "shippingInfo" => $shippingInfo);
    			$item = array("item".$i => $info);
    			$result = array_merge($result,$item);
    		}
    		echo json_encode($result);
    	}
    	function basic($xml, $i) {
    		$basic = array(
				"title" => (string)$xml->searchResult->item[$i]->title,
    			"viewItemURL" => (string)$xml->searchResult->item[$i]->viewItemURL,
    			"gallerURL" => (string)$xml->searchResult->item[$i]->galleryURL,
    			"pictureURLSuperSize" => (string)$xml->searchResult->item[$i]->pictureURLSuperSize,
    			"convertedCurrentPrice" => (string)$xml->searchResult->item[$i]->sellingStatus->convertedCurrentPrice,
    			"shippingServiceCost" => (string)$xml->searchResult->item[$i]->shippingInfo->shippingServiceCost,
    			"conditionDisplayName" => (string)$xml->searchResult->item[$i]->condition->conditionDisplayName,
    			"listingType" => (string)$xml->searchResult->item[$i]->listingInfo->listingType,
    			"location" => (string)$xml->searchResult->item[$i]->location,
    			"categoryName" => (string)$xml->searchResult->item[$i]->primaryCategory->categoryName,
    			"topRatedListing" => (string)$xml->searchResult->item[$i]->topRatedListing   				
    		);
    		return $basic;
    	}
    	function seller($xml, $i) {
    		$seller = array(
    			"sellerUserName" => (string)$xml->searchResult->item[$i]->sellerInfo->sellerUserName,
    			"feedbackScore" => (string)$xml->searchResult->item[$i]->sellerInfo->feedbackScore,
    			"positiveFeedbackPercent" => (string)$xml->searchResult->item[$i]->sellerInfo->positiveFeedbackPercent,
    			"feedbackRatingStar" => (string)$xml->searchResult->item[$i]->sellerInfo->feedbackRatingStar,
    			"topRatedSeller" => (string)$xml->searchResult->item[$i]->sellerInfo->topRatedSeller,
    			"sellerStoreName" => (string)$xml->searchResult->item[$i]->storeInfo->storeName,
    			"sellerStoreURL" => (string)$xml->searchResult->item[$i]->storeInfo->storeURL,	
    		);
    		return $seller;
    	}
    	function shipping($xml, $i) {
    		$toLocation = "";
    		foreach($xml->searchResult->item[$i]->shippingInfo->shipToLocations as $location){
    			$toLocation .= (string)$location.", ";
    		}
    		$toLocation = substr($toLocation, 0, -2);
    		$shipping = array(
    			"shippingType" => (string)$xml->searchResult->item[$i]->shippingInfo->shippingType,
    			"shipToLocations" => $toLocation,
    			"expeditedShipping" => (string)$xml->searchResult->item[$i]->shippingInfo->expeditedShipping,
    			"oneDayShippingAvailable" => (string)$xml->searchResult->item[$i]->shippingInfo->oneDayShippingAvailable,
    			"returnsAccepted" => (string)$xml->searchResult->item[$i]->returnsAccepted,
    			"handlingTime" => (string)$xml->searchResult->item[$i]->shippingInfo->handlingTime
    		);
    		return $shipping;
    	}
    	   		
/*    	
    	echo "<div id='results'>";
    	$total = $xml->paginationOutput->totalEntries;
    	if($total == 0) {
   			echo "<p style='margin-top: 30px; font-size: 40px' align='center'><b>No results found</b></p>";
   		} else {
   			echo "<p style='margin-top: 30px; font-size: 30px' align='center'><b>$total Results for {$_GET['keywords']}</b></p>";
   			echo "<table style='border: 1px solid rgb(216,216,216); margin: 15px auto;'><tbody>";
    		$searchitems = $xml->searchResult;
    		foreach($searchitems->children() as $item){
    				echo "<tr><td rowspan='6' style='border-bottom: 1px solid rgb(216,216,216)'><img src='$item->galleryURL' width='300px' height='300px'></td>";
   					echo "<td height='70px' style='vertical-align: bottom; padding: 0px 15px'><a href='$item->viewItemURL' target='_blank'>$item->title</a></td></tr>";
   					echo "<tr><td style='padding: 0px 15px'><b>Condition:</b>";
   					if($item->condition->conditionId == '1000'){
   						echo " New";
    				}elseif($item->condition->conditionId == '3000'){
    					echo " Used";
    				}elseif($item->condition->conditionId == '4000'){
    					echo " Very Good";
    				}elseif($item->condition->conditionId == '5000'){
   						echo " Good";
   					}elseif($item->condition->conditionId == '6000'){
   						echo " Acceptable";
   					}else{
    					echo " {$item->condition->conditionDisplayName}";
    				}
    				if($item->topRatedListing == 'true'){
    					echo " <img src='itemTopRated.jpg' width='95px' height='95px'>";
    				}
    				echo "</td></tr>";
   					echo "<tr><td style='padding: 0px 15px'>";
   					if($item->listingInfo->listingType == 'FixedPrice' || $item->listingInfo->listingType == 'StoreInventory' ){
   						echo "<b>Buy It Now</b>";
   					}elseif($item->listingInfo->listingType == 'Auction'){
    					echo "<b>Auction</b>";
    				}elseif($item->listingInfo->listingType == 'Classified'){
    					echo "<b>Classified Ad</b>";
    				}
    				echo "</td></tr>";
    				echo "<tr><td style='vertical-align: bottom; padding: 0px 15px'>";
   					if($item->returnsAccepted == 'true'){
   						echo "Seller accepts return";
   					}
   					echo "</td></tr>";
    				echo "<tr><td style='vertical-align: top; padding: 0px 15px'>";
    				if($item->shippingInfo->shippingServiceCost == '0.0'){
    					echo "FREE Shipping -- ";
    				}else{
    					echo "Shipping Not FREE -- ";
   					}
   					if($item->shippingInfo->expeditedShipping == 'true'){
   						echo "Expedited Shipping Available -- ";
   					}
    				echo "Handled for shipping in {$item->shippingInfo->handlingTime} day(s)";
    				echo "</td></tr>";
    				echo "<tr><td style='vertical-align: bottom; border-bottom: 1px solid rgb(216,216,216); padding: 0px 15px'>";
    				echo '<b>Price: $'.$item->sellingStatus->convertedCurrentPrice.'</b>';
    				if($item->shippingInfo->shippingServiceCost != 0.0) {
   						echo '<b> (+ $'.$item->shippingInfo->shippingServiceCost.' for shipping)</b> ';
   					}
   					echo "<i>&nbsp;&nbsp;From $item->location</i>";
    				echo "</td></tr>";
    		}
    		echo "</tbody></table>";
    	}
   		echo "</div>";
*/

?>