<?php

class My_SiteContent
{
	public function getTitle($contentName) {
		$siteContentTable = new Application_Model_SiteContent();
		$select = $siteContentTable->select()
			->where('name = ?',$contentName);
		if($siteContentRow = $siteContentTable->fetchRow($select)) {
			if($siteContentRow->title) {
				return $siteContentRow->title;
			}
		}	
		return '';
	}
	
	public function getContent($contentName) {
		$siteContentTable = new Application_Model_SiteContent();
		$select = $siteContentTable->select()
			->where('name = ?',$contentName);
		if($siteContentRow = $siteContentTable->fetchRow($select)) {
			if($siteContentRow->content) {
				return $siteContentRow->content;
			}
		}	
		return '';
	}
}