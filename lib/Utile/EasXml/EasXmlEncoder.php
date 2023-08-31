<?php
namespace OCA\EAS\Utile\EasXml;

class EasXmlEncoder{

	public const WBXML_1_0 = 0x00;
	public const WBXML_1_1 = 0x01;
	public const WBXML_1_2 = 0x02;
	public const WBXML_1_3 = 0x03;

	public const SWITCH_PAGE = 0x00;
	public const END = 0x01;
	public const ENTITY = 0x02;
	public const STR_I = 0x03;
	public const LITERAL = 0x04;
	public const EXT_I_0 = 0x40;
	public const EXT_I_1 = 0x41;
	public const EXT_I_2 = 0x42;
	public const PI = 0x43;
	public const LITERAL_C = 0x44;
	public const EXT_T_0 = 0x80;
	public const EXT_T_1 = 0x81;
	public const EXT_T_2 = 0x82;
	public const STR_T = 0x83;
	public const LITERAL_A = 0x84;
	public const EXT_0 = 0xC0;
	public const EXT_1 = 0xc1;
	public const EXT_2 = 0xC2;
	public const OPAQUE = 0xC3;
	public const LITERAL_AC = 0xC4;

	private static $_namespaces = [
		'AirSync' => 0,
		'Contacts' => 1,
		'Email' => 2,
		'Calendar' => 4,
		'Move' => 5,
		'GetItemEstimate' => 6,
		'FolderHierarchy' => 7,
		'MeetingResponse' => 8,
		'Tasks' => 9,
		'ResolveRecipients' => 10,
		'ValidateCert' => 11,
		'Contacts2' => 12,
		'Ping' => 13,
		'Provision' => 14,
		'Search' => 15,
		'Gal' => 16,
		'AirSyncBase' => 17,
		'Settings' => 18,
		'DocumentLibrary' => 19,
		'ItemOperations' => 20,
		'ComposeMail' => 21,
		'Email2' => 22,
		'Notes' => 23,
		'RightsManagement' => 24,
		'Find' => 25
	];

	/**
     * The code definitions for the wbxml encoder/decoders
     *
     * @var array
     */
    private static $_codes = [
		// #0 AirSync
		0x00 => [
			0x05 => 'Synchronize',
			0x06 => 'Replies',
			0x07 => 'Add',
			0x08 => 'Modify',
			0x09 => 'Remove',
			0x0a => 'Fetch',
			0x0b => 'SyncKey',
			0x0c => 'ClientEntryId',
			0x0d => 'ServerEntryId',
			0x0e => 'Status',
			0x0f => 'Folder',
			0x10 => 'FolderType',
			0x11 => 'Version',
			0x12 => 'FolderId',
			0x13 => 'GetChanges',
			0x14 => 'MoreAvailable',
			0x15 => 'WindowSize',
			0x16 => 'Commands',
			0x17 => 'Options',
			0x18 => 'FilterType',
			0x19 => 'Truncation',
			0x1a => 'RtfTruncation',
			0x1b => 'Conflict',
			0x1c => 'Folders',
			0x1d => 'Data',
			0x1e => 'DeletesAsMoves',
			0x1f => 'NotifyGUID',
			0x20 => 'Supported',
			0x21 => 'SoftDelete',
			0x22 => 'MIMESupport',
			0x23 => 'MIMETruncation',
			0x24 => 'Wait',
			0x25 => 'Limit',
			0x26 => 'Partial',
			// EAS 14.0
			0x27 => 'ConversationMode',
			0x28 => 'MaxItems',
			0x29 => 'HeartbeatInterval',
		],
		// #1 Contacts
		0x01 => [
			0x05 => 'Anniversary',
			0x06 => 'AssistantName',
			0x07 => 'AssistnamePhoneNumber',
			0x08 => 'Birthday',
			0x09 => 'Body',
			0x0a => 'BodySize',
			0x0b => 'BodyTruncated',
			0x0c => 'Business2PhoneNumber',
			0x0d => 'BusinessCity',
			0x0e => 'BusinessCountry',
			0x0f => 'BusinessPostalCode',
			0x10 => 'BusinessState',
			0x11 => 'BusinessStreet',
			0x12 => 'BusinessFaxNumber',
			0x13 => 'BusinessPhoneNumber',
			0x14 => 'CarPhoneNumber',
			0x15 => 'Categories',
			0x16 => 'Category',
			0x17 => 'Children',
			0x18 => 'Child',
			0x19 => 'CompanyName',
			0x1a => 'Department',
			0x1b => 'Email1Address',
			0x1c => 'Email2Address',
			0x1d => 'Email3Address',
			0x1e => 'FileAs',
			0x1f => 'FirstName',
			0x20 => 'Home2PhoneNumber',
			0x21 => 'HomeCity',
			0x22 => 'HomeCountry',
			0x23 => 'HomePostalCode',
			0x24 => 'HomeState',
			0x25 => 'HomeStreet',
			0x26 => 'HomeFaxNumber',
			0x27 => 'HomePhoneNumber',
			0x28 => 'JobTitle',
			0x29 => 'LastName',
			0x2a => 'MiddleName',
			0x2b => 'MobilePhoneNumber',
			0x2c => 'OfficeLocation',
			0x2d => 'OtherCity',
			0x2e => 'OtherCountry',
			0x2f => 'OtherPostalCode',
			0x30 => 'OtherState',
			0x31 => 'OtherStreet',
			0x32 => 'PagerNumber',
			0x33 => 'RadioPhoneNumber',
			0x34 => 'Spouse',
			0x35 => 'Suffix',
			0x36 => 'Title',
			0x37 => 'WebPage',
			0x38 => 'YomiCompanyName',
			0x39 => 'YomiFirstName',
			0x3a => 'YomiLastName',
			0x3b => 'Rtf',               // EAS 2.5 only.
			0x3c => 'Picture',
			// EAS 14.0
			0x3d => 'Alias',
			0x3e => 'WeightedRank',
		],
		// #2 Email
		0x02 => [
			0x05 => 'Attachment',
			0x06 => 'Attachments',
			0x07 => 'AttName',
			0x08 => 'AttSize',
			0x09 => 'AttOid',
			0x0a => 'AttMethod',
			0x0b => 'AttRemoved',
			0x0c => 'Body',
			0x0d => 'BodySize',
			0x0e => 'BodyTruncated',
			0x0f => 'DateReceived',
			0x10 => 'DisplayName',
			0x11 => 'DisplayTo',
			0x12 => 'Importance',
			0x13 => 'MessageClass',
			0x14 => 'Subject',
			0x15 => 'Read',
			0x16 => 'To',
			0x17 => 'Cc',
			0x18 => 'From',
			0x19 => 'Reply-To',
			0x1a => 'AllDayEvent',
			0x1b => 'Categories',  // EAS 14.0
			0x1c => 'Category',    // EAS 14.0
			0x1d => 'DtStamp',
			0x1e => 'EndTime',
			0x1f => 'InstanceType',
			0x20 => 'BusyStatus',
			0x21 => 'Location',
			0x22 => 'MeetingRequest',
			0x23 => 'Organizer',
			0x24 => 'RecurrenceId',
			0x25 => 'Reminder',
			0x26 => 'ResponseRequested',
			0x27 => 'Recurrences',
			0x28 => 'Recurrence',
			0x29 => 'Type',
			0x2a => 'Until',
			0x2b => 'Occurrences',
			0x2c => 'Interval',
			0x2d => 'DayOfWeek',
			0x2e => 'DayOfMonth',
			0x2f => 'WeekOfMonth',
			0x30 => 'MonthOfYear',
			0x31 => 'StartTime',
			0x32 => 'Sensitivity',
			0x33 => 'TimeZone',
			0x34 => 'GlobalObjId',
			0x35 => 'ThreadTopic',
			0x36 => 'MIMEData',
			0x37 => 'MIMETruncated',
			0x38 => 'MIMESize',
			0x39 => 'InternetCPID',
			// EAS 12.0
			0x3a => 'Flag',
			0x3b => 'FlagStatus',
			0x3c => 'ContentClass',
			0x3d => 'FlagType',
			0x3e => 'CompleteTime',
			// EAS 14.0
			0x3f => 'DisallowNewTimeProposal',
		],
		// #3 AirNotify is deprecated
		// #4 Calendar
		0x04 => [
			0x05 => 'Timezone',
			0x06 => 'AllDayEvent',
			0x07 => 'Attendees',
			0x08 => 'Attendee',
			0x09 => 'Email',
			0x0a => 'Name',
			0x0b => 'Body',          // 2.5 Only
			0x0c => 'BodyTruncated', // 2.5 Only
			0x0d => 'BusyStatus',
			0x0e => 'Categories',
			0x0f => 'Category',
			0x10 => 'Rtf',           // 2.5 ONly
			0x11 => 'DtStamp',
			0x12 => 'EndTime',
			0x13 => 'Exception',
			0x14 => 'Exceptions',
			0x15 => 'Deleted',
			0x16 => 'ExceptionStartTime',
			0x17 => 'Location',
			0x18 => 'MeetingStatus',
			0x19 => 'OrganizerEmail',
			0x1a => 'OrganizerName',
			0x1b => 'Recurrence',
			0x1c => 'Type',
			0x1d => 'Until',
			0x1e => 'Occurrences',
			0x1f => 'Interval',
			0x20 => 'DayOfWeek',
			0x21 => 'DayOfMonth',
			0x22 => 'WeekOfMonth',
			0x23 => 'MonthOfYear',
			0x24 => 'Reminder',
			0x25 => 'Sensitivity',
			0x26 => 'Subject',
			0x27 => 'StartTime',
			0x28 => 'UID',
			// EAS 12.0
			0x29 => 'AttendeeStatus',
			0x2A => 'AttendeeType',
			// EAS 12.1 (Apparently no longer documented).
			0x2B => 'Attachment',
			0x2C => 'Attachments',
			0x2D => 'AttName',
			0x2E => 'AttSize',
			0x2F => 'AttOid',
			0x30 => 'AttMethod',
			0x31 => 'AttRemoved',
			0x32 => 'DisplayName',
			// EAS 14
			0x33 => 'DisallowNewTimeProposal',
			0x34 => 'ResponseRequested',
			0x35 => 'AppointmentReplyTime',
			0x36 => 'ResponseType',
			0x37 => 'CalendarType',
			0x38 => 'IsLeapMonth',
			// EAS 14.1
			0x39 => 'FirstDayOfWeek',
			0x3a => 'OnlineMeetingConfLink',
			0x3b => 'OnlineMeetingExternalLink',
			// EAS 16.0
			0x3c => 'ClientUid',
		],
		// #5 Move
		0x05 => [
			0x05 => 'Moves',
			0x06 => 'Move',
			0x07 => 'SrcMsgId',
			0x08 => 'SrcFldId',
			0x09 => 'DstFldId',
			0x0a => 'Response',
			0x0b => 'Status',
			0x0c => 'DstMsgId',
		],
		// #6 GetItemEstimate
		0x06 => [
			0x05 => 'GetItemEstimate',
			0x06 => 'Version',    // 12.1
			0x07 => 'Folders',
			0x08 => 'Folder',
			0x09 => 'FolderType', // 12.1
			0x0a => 'FolderId',
			0x0b => 'DateTime',   // 12.1
			0x0c => 'Estimate',
			0x0d => 'Response',
			0x0e => 'Status',
		],
		// #7 FolderHierarchy
		0x07 => [
			'Folders' => 0x05,
			'Folder' => 0x06,
			'DisplayName' => 0x07,
			'ServerEntryId' => 0x08,
			'ParentId' => 0x09,
			'Type' => 0x0a,
			'Response' => 0x0b,
			'Status' => 0x0c,
			'ContentClass' => 0x0d,
			'Changes' => 0x0e,
			'Add' => 0x0f,
			'Remove' => 0x10,
			'Update' => 0x11,
			'SyncKey' => 0x12,
			'FolderCreate' => 0x13,
			'FolderDelete' => 0x14,
			'FolderUpdate' => 0x15,
			'FolderSync' => 0x16,
			'Count' => 0x17,
			'Version' => 0x18
		],
		// #8 MeetingResponse
		0x08 => [
			0x05 => 'CalendarId',
			0x06 => 'FolderId',
			0x07 => 'MeetingResponse',
			0x08 => 'RequestId',
			0x09 => 'Request',
			0x0a => 'Result',
			0x0b => 'Status',
			0x0c => 'UserResponse',
			0x0d => 'Version',
			// EAS 14.1
			0x0e => 'InstanceId',
			// EAS 16.0
			0x12 => 'SendResponse',
		],
		// #9 Tasks
		0x09 => [
			0x05 => 'Body',
			0x06 => 'BodySize',
			0x07 => 'BodyTruncated',
			0x08 => 'Categories',
			0x09 => 'Category',
			0x0a => 'Complete',
			0x0b => 'DateCompleted',
			0x0c => 'DueDate',
			0x0d => 'UtcDueDate',
			0x0e => 'Importance',
			0x0f => 'Recurrence',
			0x10 => 'Type',
			0x11 => 'Start',
			0x12 => 'Until',
			0x13 => 'Occurrences',
			0x14 => 'Interval',
			0x16 => 'DayOfWeek',
			0x15 => 'DayOfMonth',
			0x17 => 'WeekOfMonth',
			0x18 => 'MonthOfYear',
			0x19 => 'Regenerate',
			0x1a => 'DeadOccur',
			0x1b => 'ReminderSet',
			0x1c => 'ReminderTime',
			0x1d => 'Sensitivity',
			0x1e => 'StartDate',
			0x1f => 'UtcStartDate',
			0x20 => 'Subject',
			0x21 => 'Rtf',
			// EAS 12.0
			0x22 => 'OrdinalDate',
			0x23 => 'SubOrdinalDate',
			// EAS 14.0
			0x24 => 'CalendarType',
			0x25 => 'IsLeapMonth',
			// EAS 14.1
			0x26 => 'FirstDayOfWeek',
		],
		// 10 ResolveRecipients
		0x0A => [
			0x05 => 'ResolveRecipients',
			0x06 => 'Response',
			0x07 => 'Status',
			0x08 => 'Type',
			0x09 => 'Recipient',
			0x0a => 'DisplayName',
			0x0b => 'EmailAddress',
			0x0c => 'Certificates',
			0x0d => 'Certificate',
			0x0e => 'MiniCertificate',
			0x0f => 'Options',
			0x10 => 'To',
			0x11 => 'CertificateRetrieval',
			0x12 => 'RecipientCount',
			0x13 => 'MaxCertificates',
			0x14 => 'MaxAmbiguousRecipients',
			0x15 => 'CertificateCount',
			0x16 => 'Availability',
			0x17 => 'StartTime',
			0x18 => 'EndTime',
			0x19 => 'MergedFreeBusy',
			// 14.1
			0x1a => 'Picture',
			0x1b => 'MaxSize',
			0x1c => 'Data',
			0x1d => 'MaxPictures',
		],
		// #11 ValidateCert
		0x0B => [
			0x05 => 'ValidateCert',
			0x06 => 'Certificates',
			0x07 => 'Certificate',
			0x08 => 'CertificateChain',
			0x09 => 'CheckCRL',
			0x0a => 'Status',
		],
		// #12 Contacts2
		0x0C => [
			0x05 => 'CustomerId',
			0x06 => 'GovernmentId',
			0x07 => 'IMAddress',
			0x08 => 'IMAddress2',
			0x09 => 'IMAddress3',
			0x0a => 'ManagerName',
			0x0b => 'CompanyMainPhone',
			0x0c => 'AccountName',
			0x0d => 'NickName',
			0x0e => 'MMS',
		],
		// #13 Ping
		0x0D => [
			0x05 => 'Ping',
			0x06 => 'AutdState',
			0x07 => 'Status',
			0x08 => 'HeartbeatInterval',
			0x09 => 'Folders',
			0x0a => 'Folder',
			0x0b => 'ServerEntryId',
			0x0c => 'FolderType',
			0x0d => 'MaxFolders',
		],
		// #14 Provision
		0x0E => [
			'Provision' => 0x05,
			'Policies' => 0x06,
			'Policy' => 0x07,
			'PolicyType' => 0x08,
			'PolicyKey' => 0x09,
			'Data' => 0x0A,
			'Status' => 0x0B,
			'RemoteWipe' => 0x0C,
			'EASProvisionDoc' => 0x0D,
			// EAS 12.0
			'DevicePasswordEnabled' => 0x0E,
			'AlphanumericDevicePasswordRequired' => 0x0F,
			'DeviceEncryptionEnabled' => 0x10,
			'PasswordRecoveryEnabled' => 0x11,
			'DocumentBrowseEnabled' => 0x12,
			'AttachmentsEnabled' => 0x13,
			'MinDevicePasswordLength' => 0x14,
			'MaxInactivityTimeDeviceLock' => 0x15,
			'MaxDevicePasswordFailedAttempts' => 0x16,
			'MaxAttachmentSize' => 0x17,
			'AllowSimpleDevicePassword' => 0x18,
			'DevicePasswordExpiration' => 0x19,
			'DevicePasswordHistory' => 0x1A,
			// EAS 12.1
			'AllowStorageCard' => 0x1B,
			'AllowCamera' => 0x1C,
			'RequireDeviceEncryption' => 0x1D,
			'AllowUnsignedApplications' => 0x1E,
			'AllowUnsignedInstallationPackages' => 0x1F,
			'MinDevicePasswordComplexCharacters' => 0x20,
			'AllowWiFi' => 0x21,
			'AllowTextMessaging' => 0x22,
			'AllowPOPIMAPEmail' => 0x23,
			'AllowBluetooth' => 0x24,
			'AllowIrDA' => 0x25,
			'RequireManualSyncWhenRoaming' => 0x26,
			'AllowDesktopSync' => 0x27,
			'MaxCalendarAgeFilter' => 0x28,
			'AllowHTMLEmail' => 0x29,
			'MaxEmailAgeFilter' => 0x2A,
			'MaxEmailBodyTruncationSize' => 0x2B,
			'MaxHTMLBodyTruncationSize' => 0x2C,
			'RequireSignedSMIMEMessages' => 0x2D,
			'RequireEncryptedSMIMEMessages' => 0x2E,
			'RequireSignedSMIMEAlgorithm' => 0x2F,
			'RequireEncryptedSMIMEAlgorithm' => 0x30,
			'AllowSMIMEEncryptionAlgorithmNegotiation' => 0x31,
			'AllowSMIMESoftCerts' => 0x32,
			'AllowBrowser' => 0x33,
			'AllowConsumerEmail' => 0x34,
			'AllowRemoteDesktop' => 0x35,
			'AllowInternetSharing' => 0x36,
			'UnapprovedInROMApplicationList' => 0x37,
			'ApplicationName' => 0x38,
			'ApprovedApplicationList' => 0x39,
			'Hash' => 0x3A,
		],
		// #15 Search
		0x0F => [
			0x05 => 'Search',
			0x07 => 'Store',
			0x08 => 'Name',
			0x09 => 'Query',
			0x0A => 'Options',
			0x0B => 'Range',
			0x0C => 'Status',
			0x0D => 'Response',
			0x0E => 'Result',
			0x0F => 'Properties',
			0x10 => 'Total',
			0x11 => 'EqualTo',
			0x12 => 'Value',
			0x13 => 'And',
			0x14 => 'Or',
			0x15 => 'FreeText',
			0x17 => 'DeepTraversal',
			0x18 => 'LongId',
			0x19 => 'RebuildResults',
			0x1A => 'LessThan',
			0x1B => 'GreaterThan',
			0x1C => 'Schema',
			0x1D => 'Supported',
			// EAS 12.1
			0x1E => 'UserName',
			0x1F => 'Password',
			0x20 => 'ConversationId',
			// EAS 14.1
			0x21 => 'Picture',
			0x22 => 'MaxSize',
			0x23 => 'MaxPictures',
		],
		// #16 GAL (Global Address List)
		0x10 => [
			0x05 => 'DisplayName',
			0x06 => 'Phone',
			0x07 => 'Office',
			0x08 => 'Title',
			0x09 => 'Company',
			0x0A => 'Alias',
			0x0B => 'FirstName',
			0x0C => 'LastName',
			0x0D => 'HomePhone',
			0x0E => 'MobilePhone',
			0x0F => 'EmailAddress',
			// 14.1
			0x10 => 'Picture',
			0x11 => 'Status',
			0x12 => 'Data',
		],
		// #17 AirSyncBase (12.0)
		0x11 => [
			0x05 => 'BodyPreference',
			0x06 => 'Type',
			0x07 => 'TruncationSize',
			0x08 => 'AllOrNone',
			0x0A => 'Body',
			0x0B => 'Data',
			0x0C => 'EstimatedDataSize',
			0x0D => 'Truncated',
			0x0E => 'Attachments',
			0x0F => 'Attachment',
			0x10 => 'DisplayName',
			0x11 => 'FileReference',
			0x12 => 'Method',
			0x13 => 'ContentId',
			0x14 => 'ContentLocation',
			0x15 => 'IsInline',
			0x16 => 'NativeBodyType',
			0x17 => 'ContentType',
			// EAS 14.0
			0x18 => 'Preview',
			// EAS 14.1
			0x19 => 'BodyPartPreference',
			0x1a => 'BodyPart',
			0x1b => 'Status',
			// EAS 16.0
			0x1c => 'Add',
			0x1d => 'Delete',
			0x1e => 'ClientId',
			0x1f => 'Content',
			0x20 => 'Location',
			0x21 => 'Annontation',
			0x22 => 'Street',
			0x23 => 'City',
			0x24 => 'State',
			0x25 => 'Country',
			0x26 => 'PostalCode',
			0x27 => 'Latitude',
			0x28 => 'Longitude',
			0x29 => 'Accuracy',
			0x2a => 'Altitude',
			0x2b => 'AltitudeAccuracy',
			0x2c => 'LocationUri',
			0x2d => 'InstanceId',
		],
		// #18 Settings
		0x12 => [
			'Settings' => 0x05,
			'Status' => 0x06,
			'Get' => 0x07,
			'Set' => 0x08,
			'Oof' => 0x09,
			'OofState' => 0x0A,
			'StartTime' => 0x0B,
			'EndTime' => 0x0C,
			'OofMessage' => 0x0D,
			'AppliesToInternal' => 0x0E,
			'AppliesToExternalKnown' => 0x0F,
			'AppliesToExternalUnknown' => 0x10,
			'Enabled' => 0x11,
			'ReplyMessage' => 0x12,
			'BodyType' => 0x13,
			'DevicePassword' => 0x14,
			'Password' => 0x15,
			'DeviceInformation' => 0x16,
			'Model' => 0x17,
			'IMEI' => 0x18,
			'FriendlyName' => 0x19,
			'OS' => 0x1A,
			'OSLanguage' => 0x1B,
			'PhoneNumber' => 0x1B,
			'UserInformation' => 0x1D,
			'EmailAddresses' => 0x1E,
			'SmtpAddress' => 0x1F,
			// EAS 12.1
			'UserAgent' => 0x20,
			// EAS 14.0
			'EnableOutboundSMS' => 0x21,
			'MobileOperator' => 0x22,
			// EAS 14.1
			'PrimarySmtpAddress' => 0x23,
			'Accounts' => 0x24,
			'Account' => 0x25,
			'AccountId' => 0x26,
			'AccountName' => 0x27,
			'UserDisplayName' => 0x28,
			'SendDisabled' => 0x29,
			'RightsManagementInformation' => 0x2B,
		],
		// #19 DocumentLibrary
		0x13 => [
			0x05 => 'LinkId',
			0x06 => 'DisplayName',
			0x07 => 'IsFolder',
			0x08 => 'CreationDate',
			0x09 => 'LastModifiedDate',
			0x0A => 'IsHidden',
			0x0B => 'ContentLength',
			0x0C => 'ContentType'
		],
		// #20 ItemOperations
		0x14 => [
			0x05 => 'ItemOperations',
			0x06 => 'Fetch',
			0x07 => 'Store',
			0x08 => 'Options',
			0x09 => 'Range',
			0x0A => 'Total',
			0x0B => 'Properties',
			0x0C => 'Data',
			0x0D => 'Status',
			0x0E => 'Response',
			0x0F => 'Version',
			0x10 => 'Schema',
			0x11 => 'Part',
			0x12 => 'EmptyFolderContent',
			0x13 => 'DeleteSubFolders',
			// EAS 12.1
			0x14 => 'UserName',
			0x15 => 'Password',
			// EAS 14.0
			0x16 => 'Move',
			0x17 => 'DstFldId',
			0x18 => 'ConversationId',
			0x19 => 'MoveAlways',
		],
		// #21 ComposeMail (14.0)
		0x15 => [
			0x05 => 'SendMail',
			0x06 => 'SmartForward',
			0x07 => 'SmartReply',
			0x08 => 'SaveInSentItems',
			0x09 => 'ReplaceMime',
			0x0A => 'Type',
			0x0B => 'Source',
			0x0C => 'FolderId',
			0x0D => 'ItemId',
			0x0E => 'LongId',
			0x0F => 'InstanceId',
			0x10 => 'MIME',
			0x11 => 'ClientId',
			0x12 => 'Status',
			// 14.1
			0x13 => 'AccountId',
			// EAS 16.0
			0x15 => 'Forwardees',
			0x16 => 'Forwardee',
			0x17 => 'ForwardeeName',
			0x18 => 'ForwardeeEmail'
		],
		// #22 Email2 (14.0)
		0x16 => [
			0x05 => 'UmCallerId',
			0x06 => 'UmUserNotes',
			0x07 => 'UmAttDuration',
			0x08 => 'UmAttOrder',
			0x09 => 'ConversationId',
			0x0A => 'ConversationIndex',
			0x0B => 'LastVerbExecuted',
			0x0C => 'LastVerbExecutionTime',
			0x0D => 'ReceivedAsBcc',
			0x0E => 'Sender',
			0x0F => 'CalendarType',
			0x10 => 'IsLeapMonth',
			// 14.1
			0x11 => 'AccountId',
			0x12 => 'FirstDayOfWeek',
			0x13 => 'MeetingMessageType',
			// EAS 16.0
			0x15 => 'IsDraft',
			0x16 => 'Bcc',
			0x17 => 'Send'
		],
		// #23 Notes (14.0)
		0x17 => [
			0x05 => 'Subject',
			0x06 => 'MessageClass',
			0x07 => 'LastModifiedDate',
			0x08 => 'Categories',
			0x09 => 'Category',
		],
		// #24 RightsManagement (14.1)
		0x18 => [
			0x05 => 'RightsManagementSupport',
			0x06 => 'RightsManagementTemplates',
			0x07 => 'RightsManagementTemplate',
			0x08 => 'RightsManagementLicense',
			0x09 => 'EditAllowed',
			0x0A => 'ReplyAllowed',
			0x0B => 'ReplyAllAllowed',
			0x0C => 'ForwardAllowed',
			0x0D => 'ModifyRecipientsAllowed',
			0x0E => 'ExtractAllowed',
			0x0F => 'PrintAllowed',
			0x10 => 'ExportAllowed',
			0x11 => 'ProgrammaticAccessAllowed',
			0x12 => 'Owner',
			0x13 => 'ContentExpiryDate',
			0x14 => 'TemplateID',
			0x15 => 'TemplateName',
			0x16 => 'TemplateDescription',
			0x17 => 'ContentOwner',
			0x18 => 'RemoveRightsManagementDistribution'
		],
		// #25 Windows Live
		0xFE => [
			0x05 => 'Annotations',
			0x06 => 'Annotation',
			0x07 => 'Name',
			0x08 => 'Value'
		]
	];

	/**
	 * WBXMLDecoder constructor
	 */
	public function __construct() {

	}

	/**
	 * convert object to string
	 * 
	 * @param $data
	 * 
	 * @throws Throwable
	 */
	public function stringFromObject($object): string {

		// construct stream object
		$stream = fopen('php://temp', 'rw');
		//
		$this->streamFromObject($stream, $object);
		// rewind to beggining of stream
		rewind($stream);
		//
		$data = stream_get_contents($stream);
		// close stream
		fclose($stream);

		return $data;

	}

	/**
	 * convert object data to stream
	 * 
	 * @param $stream	data stream
	 * 
	 * @throws Exception
	 */
	public function streamFromObject($stream, $object): void {

		// write header
		$this->_writeByte($stream, self::WBXML_1_3);   // WBXML 1.3
        $this->_writeMBUInt($stream, 1);	// Public ID 1
        $this->_writeMBUInt($stream, 106);  // UTF-8
        $this->_writeMBUInt($stream, 0); 	// string table length (0)

		$this->_writeBodyToObject($stream, $object, -1);

	}

	/**
     * write body convert object to a binary stream
     *
     * @return string  The single byte.
     */
    private function _writeBodyToObject($stream, $data, $page): void
    {
		foreach (get_object_vars($data) as $token => $property) {

			/**
			 * TODO: Handel arrays
			 */
			
			$namespace = $property->getNamespace();
			if ($page !== self::$_namespaces[$namespace]) {
				$page = self::$_namespaces[$namespace];
				$this->_writeByte($stream, self::SWITCH_PAGE);
				$this->_writeByte($stream, $page);
			}

			if ($property instanceof EasXmlObject) {
				$code = self::$_codes[$page][$token];
				// write token start
        		$this->_writeByte($stream, $code |= 0x40);
				// write contents
				$this->_writeBodyToObject($stream, $property, $page);
				// write token end
				$this->_writeByte($stream, self::END);

			}
			elseif ($property instanceof EasXmlProperty) {
				$code = self::$_codes[$page][$token];
				// write token start
        		$this->_writeByte($stream, $code |= 0x40);
				if ($property->getOpaque()) {
					// write contents
					$this->_writeOpaque($stream, $property->getContents());
				}
				else {
					// write contents
					$this->_writeString($stream, $property->getContents());
				}
				// write token end
				$this->_writeByte($stream, self::END);
			}
		}
	}

	/**
     * Writes a single byte to the stream
     *
     * @param byte $byte  The byte to output.
     */
    private function _writeByte($stream, $byte)
    {
		fwrite($stream, chr($byte));
    }

    /**
     * Writes an MBUInt to the stream
     *
     * @param $uint  The data to write.
     */
    private function _writeMBUInt($stream, $uint)
    {
        while (1) {
            $byte = $uint & 0x7f;
            $uint = $uint >> 7;
            if ($uint == 0) {
                $this->_writeByte($stream, $byte);
                break;
            } else {
                $this->_writeByte($stream, $byte | 0x80);
            }
        }
    }

	/**
     * Writes a string along with the terminator
     *
     * @param mixed $content  A string
     */
    private function _writeString($stream, $content)
    {
		$this->_writeByte($stream, self::STR_I);
        fwrite($stream, $content);
    	$this->_writeByte($stream, 0);
    }

	/**
     * Writes a opaque string along with the lenght
     *
     * @param mixed $content  A string
     */
    private function _writeOpaque($stream, $content)
    {
		$this->_writeByte($stream, self::OPAQUE);
		$this->_writeMBUInt($stream, strlen($content));
        fwrite($stream, $content);
    }

	/**
	 * @param string $input
	 * @param int $version
	 * @return string|null
	 * @throws WBXMLException
	 */
	public function encode(string $input,$version=0x03): ?string{
		$wbxml = new WBXML;
		$wbxml->setVersion($version);
		$wbxml->setPublicId(0x01);
		$wbxml->setIsIndex(false);
		$wbxml->setCharset(0x6A);
		$wbxml->setStringTable([]);

		$xml = new DOMDocument();
		if(@!$xml->loadXML($input)){
			throw new WBXMLException('Invalid XML');
		}
		$arr = $this->xmlToArray($xml->firstChild,$this->codepages);

		$wbxml->setBody($arr);

		return $wbxml->serialize();
	}

	/**
	 * @param $stream
	 * @return string|null
	 * @throws WBXMLException
	 */
	public function encodeStream($stream): ?string{
		return $this->encode(stream_get_contents($stream));
	}

	private $page = 0;

	private function xmlToArray(DOMNode $node,$codepages): array{
		$arr = [];

		if($node->hasChildNodes()){
			$tag = $this->getTagId($node,$codepages);
			if($this->page!==$tag[0]){
				$this->page = $tag[0];
				$arr[] = [self::SWITCH_PAGE,$this->page];
			}
			$arr[] = [null,$tag[1],'OPEN'];
//			if($node->hasAttributes()){
//				//TODO Attributes
//			}
			foreach($node->childNodes AS $childNode){
				$addArr = $this->xmlToArray($childNode,$codepages);
				foreach($addArr AS $add){
					$arr[] = $add;
				}
			}
			$arr[] = [self::END];
		}else{
			if($node->nodeType===XML_CDATA_SECTION_NODE){
				/**@var DOMCdataSection $node*/
				$str = trim($node->data);
				if($str!==''){
					$arr[] = [self::OPAQUE,$node->data];
				}
			}
			if($node->nodeType===XML_TEXT_NODE){
				$str = trim($node->nodeValue);
				if($str!==''){
					$arr[] = [self::STR_I,$node->nodeValue];
				}
			}else{
				$tag = $this->getTagId($node,$codepages);
				if($this->page!==$tag[0]){
					$this->page = $tag[0];
					$arr[] = [self::SWITCH_PAGE,$this->page];
				}
				$arr[] = [null,$tag[1],'SELF'];
//				if($node->hasAttributes()){
//					//TODO Attributes
//				}
			}
		}

		return $arr;
	}

	/**
	 * @param DOMNode $node
	 * @param WBXMLCodePage[] $codepages
	 * @return array
	 */
	private function getTagId(DOMNode $node,$codepages): array{
		$namespace = $node->namespaceURI;
		$localname = $node->localName;

		foreach($codepages AS $codePage){
			if($codePage->getNamespace()===$namespace){
				foreach($codePage->getCodes() AS $key=>$code){
					if($code===$localname){
						$returnKey = $key;
						if($node->hasAttributes()){
							$returnKey |= 0x80;
						}
						if($node->hasChildNodes()){
							$returnKey |= 0x40;
						}
						return [$codePage->getNumber(),$returnKey];
					}
				}
			}
		}

		return [-1,-1];
	}

}