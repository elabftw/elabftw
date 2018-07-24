/**
 * fontawesome.es.js
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 *
 * In this file we define which icons we import (tree shaking feature) from font-awesome
 * This way we don't import all the icons \o/
 */

// CORE
import { library, dom } from '@fortawesome/fontawesome-svg-core'

// SOLID
import {
    faBold,
    faChartPie,
    faCheck,
    faCheckSquare,
    faChevronCircleLeft,
    faChevronRight,
    faClipboardCheck,
    faClone,
    faCloud,
    faCode,
    faCogs,
    faComments,
    faDna,
    faDownload,
    faExclamationTriangle,
    faEye,
    faFile,
    faFileArchive,
    faFileCode,
    faFileExcel,
    faFilePdf,
    faFilePowerpoint,
    faFileVideo,
    faFileWord,
    faHeading,
    faHistory,
    faImage,
    faItalic,
    faInfoCircle,
    faLink,
    faList,
    faListOl,
    faLock,
    faLockOpen,
    faMinusCircle,
    faPaintBrush,
    faPaperclip,
    faPencilAlt,
    faPlusCircle,
    faQuestionCircle,
    faQuoteLeft,
    faSearch,
    faSignOutAlt,
    faSort,
    faSquare,
    faStar,
    faSyncAlt,
    faTags,
    faTimes,
    faUpload,
    faUser,
} from '@fortawesome/free-solid-svg-icons'

library.add(
    faBold,
    faChartPie,
    faCheck,
    faCheckSquare,
    faChevronCircleLeft,
    faChevronRight,
    faClipboardCheck,
    faClone,
    faCloud,
    faCode,
    faCogs,
    faComments,
    faDna,
    faDownload,
    faExclamationTriangle,
    faEye,
    faFile,
    faFileArchive,
    faFileCode,
    faFileExcel,
    faFilePdf,
    faFilePowerpoint,
    faFileVideo,
    faFileWord,
    faHeading,
    faHistory,
    faImage,
    faItalic,
    faInfoCircle,
    faLink,
    faList,
    faListOl,
    faLock,
    faLockOpen,
    faMinusCircle,
    faPaintBrush,
    faPaperclip,
    faPencilAlt,
    faPlusCircle,
    faQuestionCircle,
    faQuoteLeft,
    faSearch,
    faSignOutAlt,
    faSort,
    faSquare,
    faStar,
    faSyncAlt,
    faTags,
    faTimes,
    faUpload,
    faUser
)

// REGULAR
import { faCalendarAlt, faCalendarCheck, faCopy} from '@fortawesome/free-regular-svg-icons';
library.add(faCalendarAlt, faCalendarCheck, faCopy)

// BRANDS
import { faGithub, faTwitter } from '@fortawesome/free-brands-svg-icons';
library.add(faTwitter, faGithub)

// Kicks off the process of finding <i> tags and replacing with <svg>
dom.watch()
