<?php
/**
 * Created by PhpStorm.
 * User: thomasm
 * Date: 30.09.2015
 * Time: 12:14
 */

namespace Metaregistrar\EPP;


class atEppPollResponse extends eppPollResponse
{
    public function getMessageDataAsNode() {

        if ($this->getResultCode() == eppResponse::RESULT_NO_MESSAGES) {

            return null;

        } else {

            $xpath = $this->xPath();
            $xpath->registerNamespace('at-ext-message', atEppConstants::namespaceAtExtMessage);
            $result = $xpath->query('/epp:epp/epp:response/epp:resData/at-ext-message:message/at-ext-message:data');

            if ($result && isset($result->item(0)->nodeValue) && !empty($result->item(0)->nodeValue)) {
                return $result->item(0);
            }

            return null;
        }
    }
}