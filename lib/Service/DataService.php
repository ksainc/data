<?php
declare(strict_types=1);

/**
* @copyright Copyright (c) 2023 Sebastian Krupinski <krupinski01@gmail.com>
*
* @author Sebastian Krupinski <krupinski01@gmail.com>
*
* @license AGPL-3.0-or-later
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as
* published by the Free Software Foundation, either version 3 of the
* License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCA\Data\Service;

use Exception;

use Sabre\VObject\Reader;

use OCA\Data\Db\Services;
use OCA\Data\Service\ContactsService;

class DataService {

	/**
	 * @var ContactsService
	 */
    private $ContactsService;

	public function __construct(Services $Services, ContactsService $ContactsService) {
		$this->Services = $Services;
		$this->ContactsService = $ContactsService;
	}

	/**
	 * retrieve collections for specific user and data type
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		nextcloud user id
	 * @param string $type		data type
	 * 
	 * @return array 			of collection(s) and attributes
	 */
	public function listCollections(string $uid, string $type): array {

		// construct response object
		$response = [];
		// retrieve all collections
		if ($type == 'CC') {
			$response['Collections'] = $this->ContactsService->listCollections($uid);
		}
		if ($type == 'EC') {
			$response['Collections'] = []; //$this->EventsService->listCollections($uid);
		}
		if ($type == 'TC') {
			$response['Collections'] = []; //$this->TasksService->listCollections($uid);
		}
		// return response
		return $response;

	}
	
	public function generateCSV(array $service) {

		// modify service entry accessed in the data store
		$this->Services->modifyAccessed((string) $service['id'], time(), '');
		// load entities
		$entities = $this->ContactsService->listEntities($service['data_collection']);
		// document start
		yield 'ID,UID,CID,State,CreatedOn,ModifiedOn,Label,' . 
			  'NameLast,NameFirst,NameOther,NamePrefix,NameSuffix,NamePhoneticLast,NamePhoneticFirst,NamePhoneticOther,NameAliases,' .
			  'BirthDay,Gender,Partner,AnniversaryDay,Address,Phone,Email,IMPP,' . 
			  'OccupationOrganization,OccupationTitle,OccupationRole,OccupationDepartment,' . PHP_EOL;
		// document iteration
		foreach ($entities as $lo) {

			// convert to contact object
            $co = $this->ContactsService->toContactObject(Reader::read($lo['carddata']));
            $co->ID = (string) $lo['uri'];
            $co->CID = (string) $lo['addressbookid'];
            $co->ModifiedOn = new \DateTime(date("Y-m-d H:i:s", $lo['lastmodified']));
            $co->State = trim((string) $lo['etag'],'"');
			
			$csv = '';
        	$csv .= "$co->ID,";
			$csv .= "$co->UID,";
			$csv .= "$co->CID,";
			$csv .= "$co->State,";
			$csv .= ($co->CreatedOn instanceof \DateTime) ? '"' . $co->CreatedOn->format(DATE_W3C) . ';' . $co->CreatedOn->getTimeZone()->getName() . '",' : '"",';
			$csv .= ($co->ModifiedOn instanceof \DateTime) ? '"' . $co->ModifiedOn->format(DATE_W3C) . ';' . $co->ModifiedOn->getTimeZone()->getName() . '",' : '"",';
			$csv .= '"' . $co->Label . '",';
			$csv .= '"' . $co->Name->Last . '",';
			$csv .= '"' . $co->Name->First . '",';
			$csv .= '"' . $co->Name->Other . '",';
			$csv .= '"' . $co->Name->Prefix . '",';
			$csv .= '"' . $co->Name->Suffix . '",';
			$csv .= '"' . $co->Name->PhoneticLast . '",';
			$csv .= '"' . $co->Name->PhoneticFirst . '",';
			$csv .= '"' . $co->Name->PhoneticOther . '",';
			$csv .= '"' . $co->Name->Aliases . '",';
			$csv .= ($co->BirthDay instanceof \DateTime) ? '"' . $co->BirthDay->format('Y-m-d') . '",' : '"",';
			$csv .= "$co->Gender,";
			$csv .= "$co->Partner,";
			$csv .= ($co->AnniversaryDay instanceof \DateTime) ? '"' . $co->AnniversaryDay->format('Y-m-d') . '",' : '"",';
			$csv .= '"';
			foreach ($co->Address as $entry) {
				$csv .= $entry->Type . "|" . 
				addcslashes($entry->Street, '"') . "|" . 
				addcslashes($entry->Locality, '"') . "|" . 
				addcslashes($entry->Region, '"') . "|" . 
				addcslashes($entry->Code, '"') . "|" . 
				addcslashes($entry->Country, '"') . ";";
			}
			$csv .= '",';
			$csv .= '"';
			foreach ($co->Phone as $entry) {
				$csv .= $entry->Type . "|" . 
				$entry->SubType . "|" . 
				addcslashes($entry->Number, '"') . ";";
			}
			$csv .= '",';
			$csv .= '"';
			foreach ($co->Email as $entry) {
				$csv .= $entry->Type . "|" . 
				$entry->Address . ";";
			}
			$csv .= '",';
			$csv .= '"';
			foreach ($co->IMPP as $entry) {
				$csv .= $entry->Type . "|" . 
				$entry->Address . ";";
			}
			$csv .= '",';
			$csv .= '"' . $co->Occupation->Organization . '",';
			$csv .= '"' . $co->Occupation->Title . '",';
			$csv .= '"' . $co->Occupation->Role . '",';
			$csv .= '"' . $co->Occupation->Department . '",';
			$csv .= '"';
			foreach ($co->Tags as $entry) {
				$csv .= $entry . ";";
			}
			$csv .= '",';
			$csv .= '"' . $co->Notes . '",';

			yield $csv . PHP_EOL;
			
		}

	}

	public function generateJSON(array $service) {

		// modify service entry accessed in the data store
		$this->Services->modifyAccessed((string) $service['id'], time(), '');
		// load entities
		$entities = $this->ContactsService->listEntities($service['data_collection']);
		// document start
		yield '[';
		// document iteration
		$count = count($entities);
		foreach ($entities as $lo) {
			
			$count -= 1;
			// convert to contact object
            $co = $this->ContactsService->toContactObject(Reader::read($lo['carddata']));
            $co->ID = (string) $lo['uri'];
            $co->CID = (string) $lo['addressbookid'];
            $co->ModifiedOn = new \DateTime(date("Y-m-d H:i:s", $lo['lastmodified']));
            $co->State = trim((string) $lo['etag'],'"');
			
			if ($count == 0)
				yield json_encode($co);
			else {
				yield json_encode($co) . ',';
			}
			
		}
		// document end
		yield ']';

	}
	
	public function generateTemplate(array $service) {

		// modify service entry accessed in the data store
		$this->Services->modifyAccessed((string) $service['id'], time(), '');
		// instance template service
		$TemplateService = new TemplateService();
		// load template
		switch ($service['data_type']) {
			case 'CC':
				$TemplateService->fromFile(dirname(__DIR__) . '/Resources/Contacts/' . $service['format']);
				break;
			case 'EC':
				$TemplateService->fromFile(dirname(__DIR__) . '/Resources/Events/' . $service['format']);
				break;
			case 'TC':
				$TemplateService->fromFile(dirname(__DIR__) . '/Resources/Tasks/' . $service['format']);
				break;
			default:
				# code...
				break;
		}
		// load entities
		$entities = $this->ContactsService->listEntities($service['data_collection']);
		// document start
		yield $TemplateService->generateStart();
		// document iteration
		foreach ($entities as $lo) {
			// convert to contact object
            $co = $this->ContactsService->toContactObject(Reader::read($lo['carddata']));
            $co->ID = (string) $lo['uri'];
            $co->CID = (string) $lo['addressbookid'];
            $co->ModifiedOn = new \DateTime(date("Y-m-d H:i:s", $lo['lastmodified']));
            $co->State = trim((string) $lo['etag'],'"');
			
			yield $TemplateService->generateIteration($co);
		}
		// document end
		yield $TemplateService->generateEnd();

	}

}
