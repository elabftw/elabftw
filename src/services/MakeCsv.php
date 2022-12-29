<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function date;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\AbstractEntity;

/**
 * Make a CSV file from a list of id and a type
 */
class MakeCsv extends AbstractMakeCsv
{

    /** In order to export the metadata as columns, we have to run through all metadata first, 
     *   so we cache the data 
     */
    protected $entitiesData = array();
    protected $metadataColumns = array();

    public function __construct(AbstractEntity $entity, private array $idArr)
    {
        parent::__construct($entity);
    }

    /**
     * Return a nice name for the file
     */
    public function getFileName(): string
    {
        return date('Y-m-d') . '-export.elabftw.csv';
    }

 
    /**
     * Populate the entitiesData and metadataColumns arrays
     */
    protected function populateEntitiesAndMetadata()
    {
        $mdPositions = array();
        foreach ($this->idArr as $id) {
           $this->Entity->setId((int) $id);
           try {
               $permissions = $this->Entity->getPermissions();
           } catch (IllegalActionException $e) {
               continue;
           }
           if ($permissions['read']) {
               $entityData = $this->Entity->entityData;
               $entityData['url'] = $this->getUrl();
               $metadataValues = array();
               if ($entityData['metadata']) {
                   $decoded = json_decode($entityData['metadata'], true, 512);
                   if ($decoded && isset($decoded['extra_fields'])) {
                       foreach ($decoded['extra_fields'] as $field => $details) {
                           $mdPositions[$field] = intval($details['position']);
                           $metadataValues[$field] = $details['value'] ?? '';
                       }
                       
                   }
               } 
               $entityData['metadata_values'] = $metadataValues;
               $this->entitiesData[] = $entityData;
           }
        }
        asort($mdPositions);
        $this->metadataColumns = array_keys($mdPositions);
    }

    
    /**
     * Here we populate the first row: it will be the column names
     */
    protected function getHeader(): array
    {
       $this->populateEntitiesAndMetadata();
       return  array_merge(
            array('id', 'date', 'title', 'content', 'category', 'elabid', 'rating', 'url', 'metadata'),
            $this->metadataColumns);
        
    }

    /**
     * Generate an array for the requested data
     */
    protected function getRows(): array
    {
        $rows = array();
       foreach ($this->entitiesData as $entityData) {
           $row = array(
               $entityData['id'],
               $entityData['date'],
               htmlspecialchars_decode((string) $entityData['title'], ENT_QUOTES | ENT_COMPAT),
               html_entity_decode(strip_tags(htmlspecialchars_decode((string) $entityData['body'], ENT_QUOTES | ENT_COMPAT))),
               htmlspecialchars_decode((string) $entityData['category'], ENT_QUOTES | ENT_COMPAT),
               $entityData['elabid'] ?? '',
               $entityData['rating'],
               $entityData['url'],
               $entityData['metadata'] ?? '',
           );
           foreach($this->metadataColumns as $metadataField) {
               $row[] = $entityData['metadata_values'][$metadataField] ?? '';
           }
           $rows[] = $row;
       }
        return $rows;
    }
}
