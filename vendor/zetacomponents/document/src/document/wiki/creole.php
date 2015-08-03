<?php
/**
 * File containing the ezcDocumentCreoleWiki class
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package Document
 * @version //autogen//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Document handler for Creole wiki text documents.
 *
 * Creole wiki markup is a standardisation intiative for wiki markup languages,
 * which all differ more or less slightly in the used markup syntax. The
 * documentation can be found at:
 *
 * http://www.wikicreole.org/wiki/Home
 *
 * This document handler implements conversions for Crole wiki markup.
 * The tokenizer, which differs for each wiki language, can be set
 * directly, or you may use on of the other extended implementations for the
 * specific sytaxes:
 *
 * - ezcDocumentConfluenceWiki
 * - ezcDocumentCreoleWiki
 * - ezcDocumentDokuwikiWiki
 *
 * Each wiki syntax has some sort of plugin mechanism, which allows you to
 * handle the contents of a special formatted syntax element using custom
 * classes or external applications. You can register a plugin for this, which
 * then need to "parse" the element contents itself and may return random
 * docbook markup.
 *
 * The basic conversion of a wiki document into a docbook document, using the
 * default creole tokenizer, looks like:
 *
 * <code>
 *  $document = new ezcDocumentCreoleWiki();
 *  $document->loadString( '
 *  = Example text =
 *
 *  Just some exaple paragraph with a heading, some **emphasis** markup and a
 *  [[http://ezcomponents.org|link]].' );
 *
 *  $docbook = $document->getAsDocbook();
 *  echo $docbook->save();
 * </code>
 *
 * For the conversion back from docbook to wiki markup, currently only one
 * converter to creole markup has been implemented. This conversion can be used
 * like:
 *
 * <code>
 *  $docbook = new ezcDocumentDocbook();
 *  $docbook->loadFile( 'docbook.xml' );
 *
 *  $document = new ezcDocumentCreoleWiki();
 *  $document->createFromDocbook( $docbook );
 *  echo $document->save();
 * </code>
 *
 * @package Document
 * @version //autogen//
 * @mainclass
 */
class ezcDocumentCreoleWiki extends ezcDocumentWiki
{
}

?>
