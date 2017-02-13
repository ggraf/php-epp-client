<?php
namespace Metaregistrar\EPP;
/*
   <?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <response>
       <result code="1301">
         <msg>Command completed successfully; ack to dequeue</msg>
       </result>
       <msgQ count="5" id="12345">
         <qDate>2000-06-08T22:00:00.0Z</qDate>
         <msg>Transfer requested.</msg>
       </msgQ>
       <resData>
         <obj:trnData
          xmlns:obj="urn:ietf:params:xml:ns:obj-1.0">
           <obj:name>example.com</obj:name>
           <obj:trStatus>pending</obj:trStatus>
           <obj:reID>ClientX</obj:reID>
           <obj:reDate>2000-06-08T22:00:00.0Z</obj:reDate>
           <obj:acID>ClientY</obj:acID>
           <obj:acDate>2000-06-13T22:00:00.0Z</obj:acDate>
           <obj:exDate>2002-09-08T22:00:00.0Z</obj:exDate>
         </obj:trnData>
       </resData>
       <trID>
         <clTRID>ABC-12345</clTRID>
         <svTRID>54321-XYZ</svTRID>
       </trID>
     </response>
   </epp>
 */

class eppPollResponse extends eppResponse {
    function __construct() {
        parent::__construct();
    }

    function __destruct() {
        parent::__destruct();
    }


    public function getMessageId() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:msgQ/@id');
        if (is_object($result) && ($result->length > 0)) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
    }

    public function getMessageDate() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:msgQ/epp:qDate');
        if (is_object($result) && ($result->length > 0)) {
            return trim($result->item(0)->nodeValue);
        } else {
            return null;
        }
    }

    public function getMessage() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:msgQ/epp:msg');
        if (is_object($result) && ($result->length > 0)) {
            return $result->item(0)->nodeValue;
        } else {
            return null;
        }
    }

    public function getMessageCount() {
        if ($this->getResultCode() == eppResponse::RESULT_NO_MESSAGES) {
            return 0;
        } else {
            $xpath = $this->xPath();
            $result = $xpath->query('/epp:epp/epp:response/epp:msgQ/@count');
            return $result->item(0)->nodeValue;
        }
    }

    public function getMessageType() {
        if ($this->getResultCode() == eppResponse::RESULT_NO_MESSAGES) {
            return null;
        } else {
            $xpath = $this->xPath();
            $xpath->registerNamespace('at-ext-message', 'http://www.nic.at/xsd/at-ext-message-1.0');
            $result = $xpath->query('/epp:epp/epp:response/epp:resData/at-ext-message:message/@type');

            if (isset($result->item(0)->nodeValue) && !empty($result->item(0)->nodeValue)) {
                echo '____________node: ' . $result->item(0)->nodeValue;
                return $result->item(0)->nodeValue;
            }

            echo 'nil___________';
            return null;
        }
    }

    public function getMessageDataAsNode() {

        if ($this->getResultCode() == eppResponse::RESULT_NO_MESSAGES) {
            return null;
        } else {

            $xpath = $this->xPath();
            $result = $xpath->query('/epp:epp/epp:response/epp:resData/epp:message/epp:data');

            if ($result && isset($result->item(0)->nodeValue) && !empty($result->item(0)->nodeValue)) {
                return $result->item(0);
            }

            return null;
        }
    }

    public function getMessageDataFlattened() {

        $returnArray = [];

        var_dump($this->getMessageDataAsNode());

        if ((self::getElementsByTagNameFromDOMNode($this->getMessageDataAsNode() , "entry"))->length > 0) { // its something to flatten

            foreach (self::getElementsByTagNameFromDOMNode($this->getMessageDataAsNode() , "entry") as $singleEntry) {
                $returnArray[$singleEntry->getAttribute('name')] = $singleEntry->nodeValue;
            }

            return $returnArray;

        } elseif ((self::getElementsByTagNameFromDOMNode($this->getMessageDataAsNode() , "epp"))->length > 0) { // its a submessage

            $eppResponse = new \Metaregistrar\EPP\eppPollResponse();
            $eppResponse->loadXML($this->getMessageDataAsNode()->textContent);

            return $eppResponse;
        }

        return null;
    }

    public static function getElementsByTagNameFromDOMNode($domNode, $tagName) {

        $doc = new \DOMDocument();

        var_dump($domNode);

        foreach ($domNode->childNodes as $singleEntry) {
            if ($singleEntry->nodeName == $tagName) {
                $elem = $doc->appendChild($singleEntry);
            }
        }

        return $doc->childNodes;

    }

    public function getMessageTransactionAsNode() {

        if ($this->getResultCode() == eppResponse::RESULT_NO_MESSAGES) {

            return null;

        } else {

            $xpath = $this->xPath();
            $result = $xpath->query('/epp:epp/epp:response/epp:resData/epp:message/epp:data/epp:epp/epp:response/epp:trID');

            if (isset($result->item(0)->nodeValue) && !empty($result->item(0)->nodeValue)) {
                return $result->item(0);
            }

            return null;
        }
    }

    public function getMessageTransactionAsArrayFlattened() {

        $returnArray = [];

        foreach (self::getElementsByTagNameFromDOMNode($this->getMessageTransactionAsNode() , "entry") as $singleEntry) {
            $returnArray[$singleEntry->nodeName] = $singleEntry->nodeValue;
        }

        return $returnArray;
    }

    public function getDomainName() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:trnData/domain:name');
        return $result->item(0)->nodeValue;
    }

    public function getDomainTrStatus() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:trnData/domain:trStatus');
        return $result->item(0)->nodeValue;
    }

    public function getDomainRequestClientId() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:trnData/domain:reID');
        return $result->item(0)->nodeValue;
    }

    public function getDomainRequestDate() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:trnData/domain:reDate');
        return $result->item(0)->nodeValue;
    }

    public function getDomainExpirationDate() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:trnData/domain:exDate');
        return $result->item(0)->nodeValue;
    }

    public function getDomainActionDate() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:trnData/domain:acDate');
        return $result->item(0)->nodeValue;
    }

    public function getDomainActionClientId() {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:trnData/domain:acID');
        return $result->item(0)->nodeValue;
    }

}
