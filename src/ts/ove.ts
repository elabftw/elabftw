/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare global {
  interface Window {
    /* eslint-disable-next-line */
    createVectorEditor: any;
  }
}

import '@teselagen/ove';
import '@teselagen/ove/style.css';
import { anyToJson } from '@teselagen/bio-parsers';
import { notif, reloadElements } from './misc';
import { Action, Model } from './interfaces';
import { Api } from './Apiv2.class';

// DISPLAY Plasmids FILES
export function displayPlasmidViewer(about: DOMStringMap): void {
  /* eslint-disable-next-line */
  const editor: any = {};
  const ApiC = new Api();
  Array.from(document.getElementsByClassName('viewer-ove')).forEach(el => {
    const oveDivDataset = (el as HTMLDivElement).dataset;
    const viewerID = el.id;
    const isSnapGeneFile = (new URL(oveDivDataset.href, window.location.origin)).searchParams.get('f').slice(-4) === '.dna';
    const filename = oveDivDataset.href;
    const realName = oveDivDataset.realName;

    // A Blob() is almost a File(): it's just missing two properties (lastModified and a name)
    // we also add the optional (mime) type attribute
    function blobToFile(theBlob: Blob, fileName: string): File {
      return new File([theBlob], fileName, { lastModified: new Date().getTime(), type: theBlob.type });
    }

    // Save a PNG image of the current plasmid map as a new attachment
    function savePlasmidMapAsImage(opts): void {
      const reader = new FileReader();
      reader.readAsDataURL(opts.pngFile);
      reader.onloadend = function(): void {
        const params = {
          'action': Action.CreateFromString,
          'file_type': 'png',
          'real_name': realName + '.png',
          'content': reader.result,
        };
        ApiC.post(`${about.type}/${about.id}/${Model.Upload}`, params).then(() => reloadElements(['uploadsDiv']));
      };
    }

    async function parseFile(fileContent): Promise<void> {
      const parsedData = await anyToJson(fileContent, {
        fileName: realName,
        guessIfProtein: true,
      });
      // we always return an array of results because some files my contain multiple sequences
      // parsedData[0].success //either true or false
      // parsedData[0].messages //either an array of strings giving any warnings or errors generated during the parsing process
      // Test if fileContent was parsed successfully. if false: show notification
      if (parsedData.length === 0) {
        throw 'Problem with file: ' + realName;
      }

      if (parsedData[0].success === false) {
        const msg = 'Invalid DNA data in file ' + realName;
        notif({res: false, msg: msg});
        throw msg;
      }

      const parsedSequence = parsedData[0].parsedSequence;

      /* eslint-disable-next-line */
      const data: any = {};
      const convertToFeaturedDNASequence = function(openVESequence): void {
        data.sequenceData = {
          features: [],
          sequence: openVESequence.sequence,
        };
        data.registryData = {
          name: openVESequence.name,
        };
        const featureMap = {};

        for (const prop in openVESequence.features) {
          if (!Object.prototype.hasOwnProperty.call(openVESequence.features, prop))
            continue;

          const feature = openVESequence.features[prop];
          const existingFeature = featureMap[feature.id];
          if (existingFeature) {
            existingFeature.locations.push({
              genbankStart: feature.start + 1,
              end: feature.end + 1,
            });
          } else {
            featureMap[feature.id] = {
              id: feature.fid,
              type: feature.type,
              name: feature.name,
              forward: feature.forward,
              notes: [{
                name: 'note',
                value: feature.notes,
              }],
              start: feature.start,
              end: feature.end,
            };
          }
        }
        for (const property in featureMap) {
          if (!Object.prototype.hasOwnProperty.call(featureMap, property))
            continue;
          data.sequenceData.features.push(featureMap[property]);
        }
      };

      const editorProps = {
        editorName: viewerID,
        withPreviewMode: true,
        isFullscreen: false,
        showMenuBar: false,
        withRotateCircularView: false,
        showReadOnly: false,
        disableSetReadOnly: true,
        showGCContentByDefault: true,
        alwaysAllowSave: true,
        generatePng: true,
        handleFullscreenClose: function(): void { // event could be used as parameter
          editor[viewerID].close();
          reloadElements(['uploadsDiv']);
        },
        onCopy: function(event, copiedSequenceData, editorState): void {
          // the copiedSequenceData is the subset of the sequence that has been copied in the teselagen sequence format
          const clipboardData = event.clipboardData;
          clipboardData.setData('text/plain', copiedSequenceData.textToCopy);
          data.selection = editorState.selectionLayer;
          data.openVECopied = copiedSequenceData;
          convertToFeaturedDNASequence(editorState.sequenceData);
          clipboardData.setData(
            'application/json',
            JSON.stringify(data));

          event.preventDefault();
          // in onPaste in your app you can do:
          // e.clipboardData.getData('application/json')
        },
        // repurposed to save an image as new attachment of the current plasmid map
        /* eslint-disable-next-line */
        onSave: function(opts = {} as any): void { // , sequenceDataToSave, editorState, onSuccessCallback could be used as parameter
          savePlasmidMapAsImage(opts);
        },
        allowMultipleFeatureDirections: true,
        PropertiesProps: {
          // the list of tabs shown in the Properties panel
          propertiesList: [
            'general',
            'features',
            'parts',
            'primers',
            'translations',
            'cutsites',
            'orfs',
            'genbank',
          ],
        },
        ToolBarProps: {
          toolList: [
            {
              name: 'saveTool',
              tooltip: 'Save image of plasmid map',
            },
            'downloadTool',
            //'importTool',
            //'undoTool',
            //'redoTool',
            'cutsiteTool',
            'featureTool',
            'partTool',
            //'alignmentTool',
            'oligoTool', // Primers
            'orfTool',
            //'editTool',
            'findTool',
            //'visibilityTool'
          ],
        },
        StatusBarProps: {
          showCircularity: false,
          showReadOnly: true,
          showAvailability: false,
        },
      };

      editor[viewerID] = window.createVectorEditor(document.getElementById(viewerID), editorProps);

      const editorState = {
        // note, sequence data passed here will be coerced to fit the Teselagen data model
        readOnly: true,
        // Open Vector Editor data model
        sequenceData: parsedSequence,
        // clear the sequenceDataHistory if there is any left over from a previous sequence
        sequenceDataHistory: {},
        annotationVisibility: {
          features: true,
        },
        panelsShown: [
          [{
            id: 'circular',
            name: 'Plasmid Map',
            active: true,
          }, {
            id: 'rail',
            name: 'Linear Map',
            active: false,
          }],
          [{
            id: 'sequence',
            name: 'Linear Sequence Map',
            active: true,
          }, {
            id: 'properties',
            name: 'Properties',
            active: false,
          }],
        ],
      };

      // Change layout for linear sequences
      if (!parsedSequence.circular) {
        editorState.panelsShown[0][1].active = true;
        editorState.panelsShown[0].shift();
      }

      editor[viewerID].updateEditor(editorState);

      // change button text from 'Open Editor' to 'Open Viewer'
      const oveButton = document.getElementById(viewerID).firstChild.firstChild.firstChild.firstChild.firstChild.firstChild as HTMLElement;
      oveButton.innerText = 'Open Viewer';
    }

    // load DNA data either as File (.dna files Snapgene) or as String
    fetch(filename).then(response => {
      if (response.ok) {
        if (isSnapGeneFile) {
          return response.blob().then(blob => parseFile(blobToFile(blob, realName)));
        }

        return response.text().then(fileContent => parseFile(fileContent));
      }

      return Promise.reject(response.status);
    }).catch(error => console.error(error));
  });
}
