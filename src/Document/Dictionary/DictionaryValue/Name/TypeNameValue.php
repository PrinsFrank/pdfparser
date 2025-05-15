<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name;

use PrinsFrank\PdfParser\Document\Object\Decorator\Catalog;
use PrinsFrank\PdfParser\Document\Object\Decorator\DecoratedObject;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Document\Object\Decorator\GenericObject;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Document\Object\Decorator\Pages;

enum TypeNameValue: string implements NameValue {
    case _3_D = '3D';
    case _3_D_ANIMATION_STYLE = '3DAnimationStyle';
    case _3_D_B_G = '3DBG';
    case _3_D_CROSS_SECTION = '3DCrossSection';
    case _3_D_LIGHTING_SCHEME = '3DLightingScheme';
    case _3_D_MEASURE = '3DMeasure';
    case _3_D_NODE = '3DNode';
    case _3_D_REF = '3DRef';
    case _3_D_RENDER_MODE = '3DRenderMode';
    case _3_D_VIEW = '3DView';
    case ACTION = 'Action';
    case ANNOT = 'Annot';
    case BACKGROUND = 'Background';
    case BEAD = 'Bead';
    case BORDER = 'Border';
    case C_I_D_FONT = 'CIDFont';
    case C_MAP = 'CMap';
    case CATALOG = 'Catalog';
    case COLLECTION = 'Collection';
    case COLLECTION_COLORS = 'CollectionColors';
    case COLLECTION_FIELD = 'CollectionField';
    case COLLECTION_ITEM = 'CollectionItem';
    case COLLECTION_SCHEMA = 'CollectionSchema';
    case COLLECTION_SORT = 'CollectionSort';
    case COLLECTION_SPLIT = 'CollectionSplit';
    case COLLECTION_SUB_ITEM = 'CollectionSubItem';
    case CRYPT = 'Crypt';
    case CRYPT_FILTER = 'CryptFilter';
    case CRYPT_FILTER_DECODE_PARMS = 'CryptFilterDecodeParms';
    case D_PART = 'DPart';
    case D_PART_ROOT = 'DPartRoot';
    case DEVELOPER_EXTENSIONS = 'DeveloperExtensions';
    case DOC_TIME_STAMP = 'DocTimeStamp';
    case DSS = 'DSS';
    case EMBEDDED_FILE = 'EmbeddedFile';
    case ENCODING = 'Encoding';
    case ENCRYPTED_PAYLOAD = 'EncryptedPayload';
    case EX_DATA = 'ExData';
    case EXT_G_STATE = 'ExtGState';
    case EXTENSIONS = 'Extensions';
    case F_W_PARAMS = 'FWParams';
    case FILE_SPEC = 'Filespec';
    case FIXED_PRINT = 'FixedPrint';
    case FOLDER = 'Folder';
    case FONT = 'Font';
    case FONT_DESCRIPTOR = 'FontDescriptor';
    case GEO_G_C_S = 'GEOGCS';
    case GROUP = 'Group';
    case HALF_TONE = 'Halftone';
    case INLINE = 'Inline';
    case LAYOUT = 'Layout';
    case M_C_R = 'MCR';
    case MARK_INFO = 'MarkInfo';
    case MASK = 'Mask';
    case MEASURE = 'Measure';
    case MEDIA_CLIP = 'MediaClip';
    case MEDIA_CRITERIA = 'MediaCriteria';
    case MEDIA_DURATION = 'MediaDuration';
    case MEDIA_OFFSET = 'MediaOffset';
    case MEDIA_PERMISSIONS = 'MediaPermissions';
    case MEDIA_PLAY_PARAMS = 'MediaPlayParams';
    case MEDIA_PLAYER_INFO = 'MediaPlayerInfo';
    case MEDIA_PLAYERS = 'MediaPlayers';
    case MEDIA_SCREEN_PARAMS = 'MediaScreenParams';
    case METADATA = 'Metadata';
    case MIN_BIT_DEPTH = 'MinBitDepth';
    case MIN_SCREEN_SIZE = 'MinScreenSize';
    case NAMESPACE = 'Namespace';
    case NAV_NODE = 'NavNode';
    case NAVIGATOR = 'Navigator';
    case NUMBER_FORMAT = 'NumberFormat';
    case O_B_J_R = 'OBJR';
    case O_C_G = 'OCG';
    case O_C_M_D = 'OCMD';
    case O_P_I = 'OPI';
    case OBJ_STM = 'ObjStm';
    case OUTLINES = 'Outlines';
    case OUTPUT_INTENT = 'OutputIntent';
    case PAGE = 'Page';
    case PAGE_LABEL = 'PageLabel';
    case PAGES = 'Pages';
    case PAGINATION = 'Pagination';
    case PATTERN = 'Pattern';
    case PROJ_C_S = 'PROJCS';
    case PT_DATA = 'PtData';
    case RENDITION = 'Rendition';
    case RESOURCE = 'Resource';
    case REQ_HANDLER = 'ReqHandler';
    case REQUIREMENT = 'Requirement';
    case RICH_MEDIA_ACTIVATION = 'RichMediaActivation';
    case RICH_MEDIA_ANIMATION = 'RichMediaAnimation';
    case RICH_MEDIA_COMMAND = 'RichMediaCommand';
    case RICH_MEDIA_CONFIGURATION = 'RichMediaConfiguration';
    case RICH_MEDIA_CONTENT = 'RichMediaContent';
    case RICH_MEDIA_DEACTIVATION = 'RichMediaDeactivation';
    case RICH_MEDIA_INSTANCE = 'RichMediaInstance';
    case RICH_MEDIA_POSITION = 'RichMediaPosition';
    case RICH_MEDIA_PRESENTATION = 'RichMediaPresentation';
    case RICH_MEDIA_SETTINGS = 'RichMediaSettings';
    case RICH_MEDIA_WINDOW = 'RichMediaWindow';
    case S_V = 'SV';
    case S_V_CERT = 'SVCert';
    case SIG = 'Sig';
    case SIG_FIELD_LOCK = 'SigFieldLock';
    case SIG_REF = 'SigRef';
    case SLIDESHOW = 'Slideshow';
    case SOFTWARE_IDENTIFIER = 'SoftwareIdentifier';
    case SOUND = 'Sound';
    case SPIDER_CONTENT_SET = 'SpiderContentSet';
    case STREAM = 'Stream';
    case STRUCT_ELEM = 'StructElem';
    case STRUCT_TREE_ROOT = 'StructTreeRoot';
    case TEMPLATE = 'Template';
    case THREAD = 'Thread';
    case TIMESPAN = 'Timespan';
    case TRANS = 'Trans';
    case TRANSFORM_PARAMS = 'TransformParams';
    case VIEWER_PREFERENCES = 'ViewerPreferences';
    case VIEWPORT = 'Viewport';
    case VRI = 'VRI';
    case X_OBJECT = 'XObject';
    case X_REF = 'XRef';

    /** @return class-string<DecoratedObject> */
    public function getDecoratorFQN(): string {
        return match($this) {
            TypeNameValue::CATALOG => Catalog::class,
            TypeNameValue::FONT => Font::class,
            TypeNameValue::PAGE => Page::class,
            TypeNameValue::PAGES => Pages::class,
            default => GenericObject::class,
        };
    }
}
