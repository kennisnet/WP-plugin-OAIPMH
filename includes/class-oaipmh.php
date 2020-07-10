<?php

/**
 * The file that defines the core OAI-PMH class
 *
 * @link       https://www.kennisnet.nl
 * @since      1.0.0
 *
 * @package    wpoaipmh
 */
namespace wpoaipmh\OaiPmh;

use DateTime;
//use OpenSkos2\OaiPmh\Concept as OaiConcept;
use Picturae\OaiPmh\Exception\BadArgumentException;
use Picturae\OaiPmh\Exception\NoRecordsMatchException;
use Picturae\OaiPmh\Exception\IdDoesNotExistException;
use Picturae\OaiPmh\Implementation\MetadataFormatType as ImplementationMetadataFormatType;
use Picturae\OaiPmh\Implementation\RecordList as OaiRecordList;
use Picturae\OaiPmh\Implementation\Repository\Identity as ImplementationIdentity;
//use Picturae\OaiPmh\Implementation\Record;
use Picturae\OaiPmh\Implementation\Set;
use Picturae\OaiPmh\Implementation\SetList;
use Picturae\OaiPmh\Interfaces\MetadataFormatType;
//use Picturae\OaiPmh\Interfaces\Record;
use Picturae\OaiPmh\Interfaces\RecordList;
use Picturae\OaiPmh\Interfaces\Repository as InterfaceRepository;
use Picturae\OaiPmh\Interfaces\Repository\Identity;
use Picturae\OaiPmh\Interfaces\SetList as InterfaceSetList;

use Picturae\OaiPmh\Implementation\Record\Header as Header;
use Picturae\OaiPmh\Implementation\Record as Record;

class Repository implements InterfaceRepository {

    // @see filter 'wpoaipmh/oai_repositoryName'
    protected $repositoryName = 'Leraar24 OAI'; 
    
	protected $deletedRecord = ''; // @see http://www.openarchives.org/OAI/openarchivesprotocol.html#DeletedRecords
	protected $adminEmails = array(); // TODO
	protected $granularity = 'YYYY-MM-DDThh:mm:ssZ'; // FIXME? implement when reading records
	protected $compression = null; // TODO
	protected $description = null; // TODO
	
	protected $limit = 100;
	protected $offset = 0;
	
	protected $wp_oai_bridge;
	
	public function __construct() {
	    $this->wp_oai_bridge = new \wpoaipmh_OAI_WP_bridge();
	}
	
    /**
     * @return string the base URL of the repository
     */
    public function getBaseUrl() {
        return get_option('home').'/oai'; // Relative due to DTAP
    }

    public function getGranularity( ) {
    	return $this->granularity;
    }

    /**
     * @return Identity
     */
    public function identify() {
        return new ImplementationIdentity(
                    apply_filters( 'wpoaipmh/oai_repositoryName', $this->repositoryName ),
        			$this->getEarliestDateStamp(),
        			$this->deletedRecord,
        			$this->adminEmails,
        			$this->getGranularity(),
        			$this->compression,
        			$this->description);
    }

    /**
     * @return InterfaceSetList
     */
    public function listSets() {
        $items = [];
        $items[] = new Set( 'publication', 'Publicatie' );
        
        $items = apply_filters( 'wpoaipmh/oai_listsets', $items ); // Align with values of array in wpoaipmh_WP_bridge::$post_types//get_post_types()
        
        return new SetList( $items );
    }

    /**
     * @param string $token
     * @return InterfaceSetList
     */
    public function listSetsByToken( $token ) {
        $params = $this->decodeResumptionToken( $token );
        return $this->listSets();
    }

    /**
     * @param string $metadataFormat
     * @param string $identifier
     * @return Record
     */
    public function getRecord( $metadataFormat, $identifier ) {
        // Fetch record
    	$no_meta = false;
    	$limit = 1; // Primary key
    	
    	$record_data = $this->wp_oai_bridge->listRecords(null, null, null, $limit, $this->offset, $no_meta, $metadataFormat, $identifier);

        // Throw exception if it does not exists
        if (!$record_data['records'] || (array_key_exists('status', $record_data) && $record_data['status'] == 'error')) {
        	throw new NoRecordsMatchException('No records match your criteria');
        }
        
        // Just the one ..
        foreach ($record_data['records'] as $record) {
        	return $this->processRecord($record);
        }
    }

    /**
     * @param string $metadataFormat metadata format of the records to be fetch or null if only headers are fetched
     * (listIdentifiers)
     * @param DateTime $from
     * @param DateTime $until
     * @param string $set name of the set containing this record
     * @return RecordList
     */
    public function listRecords( $metadataFormat = null, DateTime $from = null, DateTime $until = null, $set = null ) {
    	$allowed_sets = self::listSets()->getItems();
    	
    	$set_is_allowed = false;
    	foreach ( $allowed_sets as $allowed_set ) {
    		if( $allowed_set->getSpec() == $set ) {
    			$set_is_allowed = true;
    		}
    	}
    	if(!$set_is_allowed) {
    		throw new BadArgumentException('Set not allowed');
    	}
    	
    	$offset = $this->offset;
		$no_meta = (!$metadataFormat OR $_GET['verb'] == 'ListIdentifiers') ? true : false;
    	
		$record_data = $this->wp_oai_bridge->listRecords($from, $until, $set, $this->limit, $offset, $no_meta, $metadataFormat);
    	
    	// Throw exception if it does not exists
    	if (!$record_data['records'] || (array_key_exists('status', $record_data) && $record_data['status'] == 'error')) {
    		throw new NoRecordsMatchException('No records match your criteria');
    	}
    	
    	$items = [];
    	foreach ($record_data['records'] as $record) {
    		$items[] = $this->processRecord($record);
    	}
    	
    	if ($offset + $this->limit >= $record_data['total']) {
    		$token = null;
    	} else {
    		// Show token only if more records exists then are shown
    		$token = $this->encodeResumptionToken($this->limit, $from, $until, $metadataFormat, $set);
    	}

        return new OaiRecordList( $items, $token );
    }

    /**
     * Creates Record object
     * @param unknown $record
     * @return \Picturae\OaiPmh\Implementation\Record
     */
    protected function processRecord($record)
    {
    	// error_log('record_id: '. $record['record_id']);
    
    	$record_meta = new \DOMDocument();
    	@$record_meta->loadXML($record['metadata']);
    
    	$record_header = new Header(
    			$record['record_id'],	//Record ID
    			$record['published_date'],	//Publish time
    			array($record['repository_id']),	//Set spec
    			($record['deleted'] == 1 || $record['published'] == 0 ? true : false)	//Deleted state
    			);
    
    	return new Record($record_header, $record_meta);
    }
    
    /**
     * @param string $token
     * @return RecordList
     */
    public function listRecordsByToken( $token ) {
        $params = $this->decodeResumptionToken( $token );
        
        $offset = $params['offset'];
        $metadataFormat = $params['metadataPrefix'];
        $set = $params['set'];
        $from = $params['from'];
        $until = $params['until'];
        $no_meta = (!$metadataFormat OR $_GET['verb'] == 'ListIdentifiers') ? true : false;
         
        $record_data = $this->wp_oai_bridge->listRecords($from, $until, $set, $this->limit, $offset, $no_meta, $metadataFormat);
         
        // Throw exception if it does not exists
        if (!$record_data['records'] || (array_key_exists('status', $record_data) && $record_data['status'] == 'error')) {
        	throw new NoRecordsMatchException('No records match your criteria');
        }
         
        $items = [];
        foreach ($record_data['records'] as $record) {
        	$items[] = $this->processRecord($record);
        }

        // Only show if there are more records available else $token = null;
        $token = $this->encodeResumptionToken(
            $params['offset'] + $this->limit,
            $params['from'],
            $params['until'],
            $params['metadataPrefix'],
            $params['set']
        );
        
        if ($offset + $this->limit >= $record_data['total']) {
        	$token = null;
        	// TODO? Add empty resumptiontoken
        }
        return new OaiRecordList( $items, $token );
    }

    /**
     * @param string $identifier
     * @return MetadataFormatType[]
     */
    public function listMetadataFormats( $identifier = null ) {
        $formats = [];
        
        /*
        $formats[] = new ImplementationMetadataFormatType(
            'oai_dc',
            'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
            'http://www.openarchives.org/OAI/2.0/oai_dc/'
        );

        $formats[] = new ImplementationMetadataFormatType(
            'oai_rdf',
            'http://www.openarchives.org/OAI/2.0/rdf.xsd',
            'http://www.w3.org/2004/02/skos/core#'
        );
		*/
        
        $formats[] = new ImplementationMetadataFormatType(
			'lom',
			'http://www.imsglobal.org/xsd/imsmd_v1p2p4.xsd',
			'http://www.imsglobal.org/xsd/imsmd_v1p2'
		);
        
        return $formats;
    }

    /**
     * Decode resumption token
     * possible properties are:
     *
     * ->offset
     * ->metadataPrefix
     * ->set
     * ->from (timestamp)
     * ->until (timestamp)
     *
     * @param string $token
     * @return array
     */
    private function decodeResumptionToken( $token ) {
        $params = ( array ) json_decode(base64_decode( $token ) );

        if ( ! empty( $params['from'] ) ) {
            $params['from'] = new \DateTime( '@' . $params['from'] );
        }

        if ( ! empty( $params['until'] ) ) {
            $params['until'] = new \DateTime( '@' . $params['until'] );
        }

        return $params;
    }

    /**
     * Get resumption token
     *
     * @param int $offset
     * @param DateTime $from
     * @param DateTime $util
     * @param string $metadataPrefix
     * @param string $set
     * @return string
     */
    private function encodeResumptionToken(
        $offset = 0,
        DateTime $from = null,
        DateTime $util = null,
        $metadataPrefix = null,
        $set = null
    ) {
        $params = [];
        $params['offset'] = $offset;
        $params['metadataPrefix'] = $metadataPrefix;
        $params['set'] = $set;
        $params['from'] = null;
        $params['until'] = null;

        if ( $from ) {
            $params['from'] = $from->getTimestamp();
        }

        if ( $util ) {
            $params['until'] = $util->getTimestamp();
        }

        return base64_encode( json_encode( $params ) );
    }

    /**
     * Get earliest modified timestamp
     * 
     * @return DateTime
     */
    protected function getEarliestDateStamp() {
    	return $this->wp_oai_bridge->get_earliest_date();
    }
}
