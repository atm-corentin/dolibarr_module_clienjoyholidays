<?php

require_once __DIR__ . '/../lib/clienjoyholidays.lib.php';

class CliEnjoyHolidaysCron
{

	public $output;

	function __construct()
	{
		global $langs, $conf;

		// Fix for cron and langs see https://github.com/Dolibarr/dolibarr/pull/12213
		if (empty($langs) || get_class($langs) !== 'Translate') {
			$langs = new Translate('', $conf);
		}

		$langs->setDefaultLang(getDolGlobalString('MAIN_LANG_DEFAULT', 'en_US'));
		$langs->loadLangs(array('main', 'admin', 'cron', 'dict'));
		if (is_callable(array($langs, 'reload'))) {
			$langs->reload('clienjyoholidays@clienjyoholidays');
		} else {
			$langs->load('clienjyoholidays@clienjyoholidays');
		}
		// End fix

	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return    int            0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		global $db, $langs, $user;

		require_once __DIR__ . '/../class/clienjoyholidays.class.php';

		$objStatic = new CliEnjoyHolidays($db);
		$objStatic->output = '';
		$db->begin();

		$sql = 'SELECT rowid ';
		$sql .='FROM '.MAIN_DB_PREFIX.'clienjoyholidays_clienjoyholidays ';
		$sql .="WHERE status = 0 AND DATE_ADD(date_creation, INTERVAL 3 WEEK ) < NOW()";
		$resql = $db->query($sql);
		if($resql){
			$num = $objStatic->db->num_rows($resql);
			if ($num > 0) {
				while ($obj = $objStatic->db->fetch_object($resql)) {
					$list[] = $obj->rowid;
				}
			}
		}else{
			return -1;
		}
		$db->commit();
		if (empty($list)){
			$this->output .= $langs->trans("NoTravelPackageFound");

		}else {
			foreach ($list as $i => $rowid) {
				$objstatic = new CliEnjoyHolidays($objStatic->db);
				 $res = $objstatic->fetch($rowid);
				if ($res < 0) return -1;
				if ($objstatic->validate($user) < 0) {
					return -1;
				}
				$this->output .= $objstatic->getNomUrl(1);
				if ($i + 1 < count($list)) $this->output .= ', ';
			}
		}
		return 0;
	}
}
