<?php

/***************************************************************************
 *            favoritelinks.class.php
 *
 *  Jul 05, 07:00:00 2009
 *  Copyright  2009  Istvan Petres (aka P.I.Julius)
 *  me@pijulius.com
 ****************************************************************************/
 
class _favoriteLinks {
	var $adminPath = 'admin/site/favoritelinks';
	static $links = array();
	
	function SQL() {
		return
			" SELECT * FROM `{favoritelinks}` " .
			" WHERE !`Deactivated`" .
			" ORDER BY `OrderID`, `ID`";		
	}
	
	// ************************************************   Admin Part
	function countAdminItems() {
		$row = sql::fetch(sql::run(
			" SELECT COUNT(*) AS `Rows`" .
			" FROM `{favoritelinks}`" .
			" LIMIT 1"));
		return $row['Rows'];
	}
	
	function setupAdmin() {
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			favoriteLinks::add(
				__('New Link'), 
				'?path='.admin::path().'#adminform');
	}
	
	function setupAdminForm(&$form) {
		$form->add(
			__('Title'),
			'Title',
			FORM_INPUT_TYPE_TEXT,
			true);
		$form->setStyle('width: 200px;');
		$form->setTooltipText(__("e.g. English"));
		
		$form->add(
			__('Link'),
			'Link',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 300px;');
		
		$form->add(
			__('Additional Options'),
			null,
			FORM_OPEN_FRAME_CONTAINER);
		
		$form->add(
			__('Deactivated'),
			'Deactivated',
			FORM_INPUT_TYPE_CHECKBOX,
			false,
			1);
		$form->setValueType(FORM_VALUE_TYPE_BOOL);
		
		$form->addAdditionalText(
			"<span class='comment' style='text-decoration: line-through;'>" .
			__("(marked with strike through)").
			"</span>");
		
		$form->add(
			__('Order'),
			'OrderID',
			FORM_INPUT_TYPE_TEXT);
		$form->setStyle('width: 50px;');
		$form->setValueType(FORM_VALUE_TYPE_INT);
		
		$form->add(
			null,
			null,
			FORM_CLOSE_FRAME_CONTAINER);
	}
	
	function verifyAdmin(&$form) {
		$reorder = null;
		$orders = null;
		$delete = null;
		$edit = null;
		$id = null;
		
		if (isset($_POST['reordersubmit']))
			$reorder = $_POST['reordersubmit'];
		
		if (isset($_POST['orders']))
			$orders = (array)$_POST['orders'];
		
		if (isset($_GET['delete']))
			$delete = $_GET['delete'];
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
		
		if ($reorder) {
			if (!$orders)
				return false;
			
			foreach($orders as $oid => $ovalue) {
				sql::run(
					" UPDATE `{favoritelinks}` " .
					" SET `OrderID` = '".(int)$ovalue."'" .
					" WHERE `ID` = '".(int)$oid."'");
			}
			
			tooltip::display(
				__("Links have been successfully re-ordered."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if ($delete) {
			if (!$this->dbDelete($id))
				return false;
			
			tooltip::display(
				__("Link has been successfully deleted."),
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$form->verify())
			return false;
			
		if ($edit) {
			if (!$this->dbEdit($id, $form->getPostArray()))
				return false;
				
			tooltip::display(
				__("Link has been successfully updated.")." " .
				"<a href='#adminform'>" .
					__("Edit") .
				"</a>",
				TOOLTIP_SUCCESS);
			
			return true;
		}
		
		if (!$newid = $this->dbAdd($form->getPostArray()))
			return false;
				
		tooltip::display(
			__("Link has been successfully created.")." " .
			"<a href='".url::uri('id, edit, delete') .
				"&amp;id=".$newid."&amp;edit=1#adminform'>" .
				__("Edit") .
			"</a>",
			TOOLTIP_SUCCESS);
		
		$form->reset();
		return true;
	}
	
	function displayAdminListHeader() {
		echo
			"<th><span class='nowrap'>".
				__("Order")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Title / Link")."</span></th>";
	}
	
	function displayAdminListHeaderOptions() {
	}
	
	function displayAdminListHeaderFunctions() {
		echo
			"<th><span class='nowrap'>".
				__("Edit")."</span></th>" .
			"<th><span class='nowrap'>".
				__("Delete")."</span></th>";
	}
	
	function displayAdminListItem(&$row) {
		echo
			"<td>" .
				"<input type='text' name='orders[".$row['ID']."]' " .
					"value='".$row['OrderID']."' " .
					"class='order-id-entry' tabindex='1' />" .
			"</td>" .
			"<td " .
				($row['Deactivated']?
					"style='text-decoration: line-through;' ":
					null).
				"class='auto-width bold'>" .
				$row['Title'] .
				"<div class='comment' style='padding-left: 10px;'>" .
					$row['Link'] .
				"</div>" .
			"</td>";
	}
	
	function displayAdminListItemOptions(&$row) {
	}
	
	function displayAdminListItemFunctions(&$row) {
		echo
			"<td align='center'>" .
				"<a class='admin-link edit' " .
					"title='".htmlspecialchars(__("Edit"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;edit=1#adminform'>" .
				"</a>" .
			"</td>" .
			"<td align='center'>" .
				"<a class='admin-link delete confirm-link' " .
					"title='".htmlspecialchars(__("Delete"), ENT_QUOTES)."' " .
					"href='".url::uri('id, edit, delete') .
					"&amp;id=".$row['ID']."&amp;delete=1'>" .
				"</a>" .
			"</td>";
	}
	
	function displayAdminListFunctions() {
		echo
			"<input type='submit' name='reordersubmit' value='".
				htmlspecialchars(__("Reorder"), ENT_QUOTES)."' class='button' /> " .
			"<input type='reset' name='reset' value='" .
				htmlspecialchars(__("Reset"), ENT_QUOTES)."' class='button' />";
	}
	
	function displayAdminList(&$rows) {
		echo
			"<form action='".url::uri('edit, delete')."' method='post'>" .
			"<table cellpadding='0' cellspacing='0' class='list'>" .
				"<thead>" .
				"<tr>";
		
		$this->displayAdminListHeader();
		$this->displayAdminListHeaderOptions();
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
			$this->displayAdminListHeaderFunctions();
					
		echo
				"</tr>" .
				"</thead>" .
				"<tbody>";
		
		$i = 0;		
		while($row = sql::fetch($rows)) {
			echo 
				"<tr".($i%2?" class='pair'":NULL).">";
			
			$this->displayAdminListItem($row);
			$this->displayAdminListItemOptions($row);
			
			if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE)
				$this->displayAdminListItemFunctions($row);
			
			echo
				"</tr>";
			
			$i++;
		}
		
		echo 
				"</tbody>" .
			"</table>" .
			"<br />";
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE) {
			$this->displayAdminListFunctions();
			
			echo
				"<div class='clear-both'></div>" .
				"<br />";
		}
				
		echo
			"</form>";
	}
	
	function displayAdminForm(&$form) {
		$form->display();
	}
	
	function displayAdminTitle($ownertitle = null) {
		admin::displayTitle(
			__('Favorite Links Administration'),
			$ownertitle);
	}
	
	function displayAdminDescription() {
	}
	
	function displayAdmin() {
		$edit = null;
		$id = null;
		
		if (isset($_GET['edit']))
			$edit = $_GET['edit'];
		
		if (isset($_GET['id']))
			$id = (int)$_GET['id'];
			
		$this->displayAdminTitle();
		$this->displayAdminDescription();
		
		echo
			"<div class='admin-content'>";
				
		$form = new form(
				($edit?
					__("Edit Link"):
					__("New Link")),
				'neweditlink');
		
		if (!$edit)
			$form->action = url::uri('id, delete, limit');
					
		$this->setupAdminForm($form);
		$form->addSubmitButtons();
		
		if ($edit) {
			$form->add(
				__('Cancel'),
				'cancel',
				 FORM_INPUT_TYPE_BUTTON);
			$form->addAttributes("onclick=\"window.location='".
				str_replace('&amp;', '&', url::uri('id, edit, delete'))."'\"");
		}
		
		$verifyok = false;
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			$verifyok = $this->verifyAdmin($form);
		}
		
		$rows = sql::run(
			" SELECT * FROM `{favoritelinks}`" .
			" WHERE 1" .
			($this->userPermissionIDs?
				" AND `ID` IN (".$this->userPermissionIDs.")":
				null) .
			" ORDER BY `OrderID`, `ID`");
			
		if (sql::rows($rows))
			$this->displayAdminList($rows);
		else
			tooltip::display(
					__("No links found."),
					TOOLTIP_NOTIFICATION);
		
		if ($this->userPermissionType == USER_PERMISSION_TYPE_WRITE &&
			(!$this->userPermissionIDs || ($edit && 
				in_array($id, explode(',', $this->userPermissionIDs)))))
		{
			if ($edit && $id && ($verifyok || !$form->submitted())) {
				$row = sql::fetch(sql::run(
					" SELECT * FROM `{favoritelinks}`" .
					" WHERE `ID` = '".$id."'"));
			
				$form->setValues($row);
			}
			
			echo
				"<a name='adminform'></a>";
			
			$this->displayAdminForm($form);
		}
		
		unset($form);
		
		echo 
			"</div>";	//admin-content
	}
	
	function dbAdd($values) {
		if (!is_array($values))
			return false;
		
		if ($values['OrderID'] == '') {
			$row = sql::fetch(sql::run(
				" SELECT `OrderID` FROM `{favoritelinks}` " .
				" ORDER BY `OrderID` DESC" .
				" LIMIT 1"));
			
			$values['OrderID'] = (int)$row['OrderID']+1;
			
		} else {
			sql::run(
				" UPDATE `{favoritelinks}` SET " .
				" `OrderID` = `OrderID` + 1" .
				" WHERE `OrderID` >= '".(int)$values['OrderID']."'");
		}
		
		$newid = sql::run(
			" INSERT INTO `{favoritelinks}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Link` = '".
				sql::escape($values['Link'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'");
			
		if (!$newid) {
			tooltip::display(
				sprintf(__("Link couldn't be added! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return $newid;
	}
	
	function dbEdit($id, $values) {
		if (!$id)
			return false;
		
		if (!is_array($values))
			return false;
		
		sql::run(
			" UPDATE `{favoritelinks}` SET ".
			" `Title` = '".
				sql::escape($values['Title'])."'," .
			" `Link` = '".
				sql::escape($values['Link'])."'," .
			" `Deactivated` = '".
				($values['Deactivated']?
					'1':
					'0').
				"'," .
			" `OrderID` = '".
				(int)$values['OrderID']."'" .
			" WHERE `ID` = '".(int)$id."'");
			
		if (sql::affected() == -1) {
			tooltip::display(
				sprintf(__("Link couldn't be updated! Error: %s"), 
					sql::error()),
				TOOLTIP_ERROR);
			return false;
		}
		
		return true;
	}
	
	function dbDelete($id) {
		if (!$id)
			return false;
		
		sql::run(
			" DELETE FROM `{favoritelinks}` " .
			" WHERE `ID` = '".$id."'");
		
		return true;
	}
	
	// ************************************************   Client Part
	static function add($title, $link) {
		if (isset(favoriteLinks::$links[$link]))
			return false;
		
		preg_match('/(\?|&)path=(.*?)(&|\'|")/i', $link, $matches);
		
		$userpermission = userPermissions::check($GLOBALS['USER']->data['ID'], 
			(isset($matches[2])?
				$matches[2]:
				null));
		
		if ($userpermission['PermissionType'])
			favoriteLinks::$links[$link] = $title;
		
		return true;
	}

	static function clear() {
		favoriteLinks::$links = array();
	}
	
	function display() {
		if (count(favoriteLinks::$links))
			favoriteLinks::add('<SPACER>', '');
		
		$rows = sql::run(
			$this->SQL());
		
		while($row = sql::fetch($rows))
			favoriteLinks::add(
				__($row['Title']), $row['Link']);
		
		if (!favoriteLinks::$links || !is_array(favoriteLinks::$links) ||
			!count(favoriteLinks::$links))
			return;
			
		if (count(favoriteLinks::$links) == 1) {
			echo
				"<div class='admin-favorite-links'>" .
					"<div tabindex='0' class='button fc'>" .
						"<a href='".key(favoriteLinks::$links)."'" .
							(preg_match('/https?:\/\//i', key(favoriteLinks::$links))?
								" target='_blank'":
								null) .
							">" .
							"<span>".favoriteLinks::$links[key(favoriteLinks::$links)]."</span>" .
						"</a>" .
					"</div>" .
				"</div>";
			
			return;
		}
		
		$i = 1;
		foreach(favoriteLinks::$links as $link => $title) {
			$target = null;
			if (preg_match('/https?:\/\//i', $link))
				$target = " target='_blank'";
			
			if ($i == 1) {
				echo
					"<div class='admin-favorite-links'>" .
						"<div tabindex='0' class='button fc'>" .
							"<a class='fc-title'>&nbsp;</a>" .
							"<a href='".$link."'".$target.">" .
								"<span>".$title."</span>" .
							"</a>" .
							"<div class='fc-content'>" .
							"<ul>";
			} else {
				if ($title == '<SPACER>')
					echo
						"<li>" .
							"<div class='spacer'></div>" .
						"</li>";
				else
					echo
						"<li>" .
							"<a href='".$link."'".$target.">" .
								"<span>".$title."</span>" .
							"</a>" .
						"</li>";
			}
			
			$i++;
		}
		
		echo
							"</ul>" .
							"</div>" .
						"</div>" .
					"</div>";
	}
}

?>