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
 * \file    clienjoyholidays/core/boxes/clienjoyholidayswidget1.php
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
class clienjoyholidayswidget1 extends ModeleBoxes
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = "clienjoyholidaysbox";

	/**
	 * @var string Box icon (in configuration page)
	 * Automatically calls the icon named with the corresponding "object_" prefix
	 */
	public $boximg = "clienjoyholidays@clienjoyholidays";

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
	 * @var string    Widget type ('graph' means the widget is a graph widget)
	 */
	public $widgettype = 'graph';


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param string $param More parameters
	 */
	public function __construct(DoliDB $db, $param = '')
	{
		global $user, $conf, $langs;
		// Translations
		$langs->loadLangs(array("boxes", "clienjoyholidays@clienjoyholidays"));

		parent::__construct($db, $param);

		$this->boxlabel = $langs->trans("CEHWidgetLabel");

		$this->param = $param;

		// Condition when module is enabled or not
		// $this->enabled = getDolGlobalInt('MAIN_FEATURES_LEVEL') > 0;
		// Condition when module is visible by user (test on permission)
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
		global $langs;

		// Use configuration value for max lines count
		$this->max = $max;


		dol_include_once("/clienjoyholidays/class/clienjoyholidays.class.php");
		$clienjoyholidaysstatic = new CliEnjoyHolidays($this->db);

		// Populate the head at runtime
		$text = $langs->trans("CliEnjoyHolidaysBoxDescription", $max);


		$this->info_box_head = array('text' => $langs->trans("BoxTitleLast".(getDolGlobalString('MAIN_LASTBOX_ON_OBJECT_DATE') ? "" : "Modified")."CliEnjoyHolidays", $max));



		$i = 0;
		// list the summary of the orders
		if ($user->hasRight('projet', 'lire')) {
			include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
			include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			$projectstatic = new Project($this->db);
			$companystatic = new Societe($this->db);

			$socid = 0;
			//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignement.

			// Get list of project id allowed to user (in a string list separated by coma)
			$projectsListId = '';
			if (!$user->hasRight('projet', 'all', 'lire')) {
				$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, $socid);
			}

			$sql = "SELECT p.rowid, p.ref, p.title, p.fk_statut as status, p.public, p.fk_soc,";
			$sql .= " s.nom as name, s.name_alias";
			$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
			$sql .= " WHERE p.entity IN (".getEntity('project').")"; // Only current entity or severals if permission ok
			$sql .= " AND p.fk_statut = ".((int) $projectstatic::STATUS_VALIDATED); // Only open projects
			if (!$user->hasRight('projet', 'all', 'lire')) {
				$sql .= " AND p.rowid IN (".$this->db->sanitize($projectsListId).")"; // public and assigned to, or restricted to company for external users
			}

			$sql .= " ORDER BY p.datec DESC";
			//$sql.= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);

			if ($result) {
				$num = $this->db->num_rows($result);
				while ($i < min($num, $max)) {
					$objp = $this->db->fetch_object($result);

					$projectstatic->id = $objp->rowid;
					$projectstatic->ref = $objp->ref;
					$projectstatic->title = $objp->title;
					$projectstatic->public = $objp->public;
					$projectstatic->statut = $objp->status;

					$companystatic->id = $objp->fk_soc;
					$companystatic->name = $objp->name;
					$companystatic->name_alias = $objp->name_alias;

					$this->info_box_contents[$i][] = array(
						'td' => 'class="nowraponall"',
						'text' => $projectstatic->getNomUrl(1),
						'asis' => 1
					);

					$this->info_box_contents[$i][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth200onsmartphone"',
						'text' => $objp->title,
					);

					$this->info_box_contents[$i][] = array(
						'td' => 'class="tdoverflowmax100"',
						'text' => ($objp->fk_soc > 0 ? $companystatic->getNomUrl(1) : ''),
						'asis' => 1
					);

					$sql = "SELECT count(*) as nb, sum(progress) as totprogress";
					$sql .= " FROM ".MAIN_DB_PREFIX."projet as p LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt on pt.fk_projet = p.rowid";
					$sql .= " WHERE p.entity IN (".getEntity('project').')';
					$sql .= " AND p.rowid = ".((int) $objp->rowid);

					$resultTask = $this->db->query($sql);
					if ($resultTask) {
						$objTask = $this->db->fetch_object($resultTask);
						$this->info_box_contents[$i][] = array(
							'td' => 'class="right"',
							'text' => $objTask->nb."&nbsp;".$langs->trans("Tasks"),
						);
						if ($objTask->nb > 0) {
							$this->info_box_contents[$i][] = array(
								'td' => 'class="right"',
								'text' => round($objTask->totprogress / $objTask->nb, 0)."%",
							);
						} else {
							$this->info_box_contents[$i][] = array('td' => 'class="right"', 'text' => "N/A&nbsp;");
						}
						$totalnbTask += $objTask->nb;
					} else {
						$this->info_box_contents[$i][] = array('td' => 'class="right"', 'text' => round(0));
						$this->info_box_contents[$i][] = array('td' => 'class="right"', 'text' => "N/A&nbsp;");
					}
					$this->info_box_contents[$i][] = array('td' => 'class="right"', 'text' => $projectstatic->getLibStatut(3));

					$i++;
				}
				if ($max < $num) {
					$this->info_box_contents[$i][] = array('td' => 'colspan="6"', 'text' => '...');
					$i++;
				}
			}
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
		// You may make your own code here…
		// … or use the parent's class function using the provided head and contents templates
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
