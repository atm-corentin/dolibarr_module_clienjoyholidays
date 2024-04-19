<?php
/* Copyright (C) 2004-2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2023  Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2024 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    clienjoyholidays/core/boxes/box_clienjoyholidays.php
 * \ingroup clienjoyholidays
 * \brief   Widget provided by CliEnjoyHolidays
 *
 * Put detailed description here.
 */

include_once DOL_DOCUMENT_ROOT . "/core/boxes/modules_boxes.php";


/**
 * Class to manage the box
 *
 * Warning: for the box to be detected correctly by dolibarr,
 * the filename should be the lowercase classname
 */
class box_clienjoyholidays extends ModeleBoxes
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = "clienjoyholidaysbox";

	/**
	 * @var string Box icon (in configuration page)
	 * Automatically calls the icon named with the corresponding "object_" prefix
	 */
	public $boximg = 'fa-plane';

	/**
	 * @var string Box label (in configuration page)
	 */
	public $boxlabel;

	/**
	 * @var string[] Module dependencies
	 */
	public $depends = array('clienjoyholidays');

	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var mixed More parameters
	 */
	public $param;

	/**
	 * @var array Header informations. Usually created at runtime by loadBox().
	 */
	public $info_box_head = array();

	/**
	 * @var array Contents informations. Usually created at runtime by loadBox().
	 */
	public $info_box_contents = array();


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param string $param More parameters
	 */
	public function __construct(DoliDB $db, $param = '')
	{
		global $user, $langs;
		// Translations
		$langs->loadLangs(array("boxes", "clienjoyholidays@clienjoyholidays"));

		parent::__construct($db, $param);

		$this->boxlabel = $langs->trans("CEHWidgetLabel");


		$this->hidden = !$user->hasRight('clienjoyholidays', 'clienjoyholidays', 'read');
	}

	/**
	 * Load data into info_box_contents array to show array later. Called by Dolibarr before displaying the box.
	 *
	 * @param int $max Maximum number of records to load
	 * @return void
	 */
	public function loadBox($max = 5)
	{
		global $langs, $user, $conf;
		// Use configuration value for max lines count
		$this->max = $max;


		dol_include_once("/clienjoyholidays/class/clienjoyholidays.class.php");


		$clienjoyholidaysstatic = new CliEnjoyHolidays($this->db);

		$text = $langs->trans("BoxTitleLast" . (getDolGlobalString('MAIN_LASTBOX_ON_OBJECT_DATE') ? "" : "Modified") . "CliEnjoyHolidays", $max);
		$this->info_box_head = array(
			'text' => $text,
			'subtext'=>$langs->trans("Filter"),
			'subpicto' => $clienjoyholidaysstatic->picto
		);

		if ($user->hasRight('clienjoyholidays', 'clienjoyholidays', 'read')) {
			$sql = "SELECT c.rowid, c.ref , c.label , c.amount";
			$sql .= " FROM " . MAIN_DB_PREFIX . "clienjoyholidays_clienjoyholidays as c";
			$sql .= " ORDER BY date_creation DESC";
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);
				$now = dol_now();

				$line = 0;

				while ($line < $num) {
					$objp = $this->db->fetch_object($result);

					$clienjoyholidaysstatic->ref = $objp->ref;
					$clienjoyholidaysstatic->label = $objp->label;
					$clienjoyholidaysstatic->amount = $objp->amount;
					$clienjoyholidaysstatic->id = $objp->rowid;


					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $clienjoyholidaysstatic->getNomUrl(1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $clienjoyholidaysstatic->label,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => price($objp->amount, 0, $langs, 0, -1, -1, $conf->currency),
					);


					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text' => $langs->trans("NoRecordedClienjoyHolidays"),
					);
				}

				$this->db->free($result);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength' => 500,
					'text' => ($this->db->error() . ' sql=' . $sql),
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover left"',
				'text' => '<span class="opacitymedium">' . $langs->trans("ReadPermissionNotAllowed") . '</span>'
			);
		}

	}

	/**
	 * Method to show box. Called by Dolibarr eatch time it wants to display the box.
	 *
	 * @param array $head Array with properties of box title
	 * @param array $contents Array with properties of box lines
	 * @param int $nooutput No print, only return string
	 * @return string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
