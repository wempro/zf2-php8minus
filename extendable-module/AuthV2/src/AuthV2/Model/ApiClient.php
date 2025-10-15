<?php
namespace AuthV2\Model;


use Lib3rdParty\Helper\Db\AbstractTableData;

class ApiClient extends AbstractTableData {
	const API_CLIENT_STATUS_ENABLED='enabled';
	const API_CLIENT_STATUS_DISABLED='disabled';
	public $id;
	public $textId;
	public $apiClientStatus;
	public $secret;
	public $created;
	public $updated;
	public static function getMyName(){ return 'ApiClient'; }
}

