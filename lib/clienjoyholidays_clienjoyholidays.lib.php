<?php
/* Copyright (C) 2024 SuperAdmin
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
 * \file    lib/clienjoyholidays_clienjoyholidays.lib.php
 * \ingroup clienjoyholidays
 * \brief   Library files with common functions for CliEnjoyHolidays
 */

/**
 * Prepare array of tabs for CliEnjoyHolidays
 *
 * @param	CliEnjoyHolidays	$object		CliEnjoyHolidays
 * @return 	array					Array of tabs
 */
function clienjoyholidaysPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("clienjoyholidays@clienjoyholidays");

	$showtabofpagecontact = 1;
	$showtabofpagenote = 1;
	$showtabofpagedocument = 1;
	$showtabofpageagenda = 1;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/clienjoyholidays/clienjoyholidays_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("CEHTiltle");
	$head[$h][2] = 'card';
	$h++;

	if ($showtabofpagecontact) {
		$head[$h][0] = dol_buildpath("/clienjoyholidays/clienjoyholidays_contact.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Contacts");
		$head[$h][2] = 'contact';
		$h++;
	}

	if ($showtabofpagenote) {
		if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
			$nbNote = 0;
			if (!empty($object->note_private)) {
				$nbNote++;
			}
			if (!empty($object->note_public)) {
				$nbNote++;
			}
			$head[$h][0] = dol_buildpath('/clienjoyholidays/clienjoyholidays_note.php', 1).'?id='.$object->id;
			$head[$h][1] = $langs->trans('Notes');
			if ($nbNote > 0) {
				$head[$h][1] .= (!getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
			}
			$head[$h][2] = 'note';
			$h++;
		}
	}

	if ($showtabofpagedocument) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$upload_dir = $conf->clienjoyholidays->dir_output."/clienjoyholidays/".dol_sanitizeFileName($object->ref);
		$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
		$nbLinks = Link::count($db, $object->element, $object->id);
		$head[$h][0] = dol_buildpath("/clienjoyholidays/clienjoyholidays_document.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Documents');
		if (($nbFiles + $nbLinks) > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
		}
		$head[$h][2] = 'document';
		$h++;
	}

	if ($showtabofpageagenda) {
		$head[$h][0] = dol_buildpath("/clienjoyholidays/clienjoyholidays_agenda.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Events");
		$head[$h][2] = 'agenda';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@clienjoyholidays:/clienjoyholidays/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@clienjoyholidays:/clienjoyholidays/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'clienjoyholidays@clienjoyholidays');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'clienjoyholidays@clienjoyholidays', 'remove');

	return $head;
}
