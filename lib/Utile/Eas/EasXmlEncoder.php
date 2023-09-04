<?php
//declare(strict_types=1);

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

namespace OCA\EAS\Utile\Eas;

use OCA\EAS\Utile\Eas\EasXml;

class EasXmlEncoder{

	//https://learn.microsoft.com/en-us/previous-versions/office/developer/exchange-server-interoperability-guidance/hh361570(v=exchg.140)

	/**
	 * TODO: Add encode from xml
	 */
	
	 /**
     * namespace definitions to wbxml codes
     *
     * @var array
     */
	private static $_namespaces = [
		'AirSync' => 0x00,
		'Contacts' => 0x01,
		'Email' => 0x02,
		'Calendar' => 0x04,
		'Move' => 0x05,
		'GetItemEstimate' => 0x06,
		'FolderHierarchy' => 0x07,
		'MeetingResponse' => 0x08,
		'Tasks' => 0x09,
		'ResolveRecipients' => 0x0A,
		'ValidateCert' => 0x0B,
		'Contacts2' => 0x0C,
		'Ping' => 0x0D,
		'Provision' => 0x0E,
		'Search' => 0x0F,
		'Gal' => 0x10,
		'AirSyncBase' => 0x11,
		'Settings' => 0x12,
		'DocumentLibrary' => 0x13,
		'ItemOperations' => 0x14,
		'ComposeMail' => 0x15,
		'Email2' => 0x16,
		'Notes' => 0x17,
		'RightsManagement' => 0x18,
		'Find' => 0x19,
		'WindowsLive' => 0xFE
	];

	/**
     * property definitions to wbxml codes
     *
     * @var array
     */
    public static $_codes = [
		// #0 AirSync
		0x00 => [
			'Sync' => 0x05,
			'Responses' => 0x06,
			'Add' => 0x07,
			'Modify' => 0x08,
			'Delete' => 0x09,
			'Fetch' => 0x0A,
			'SyncKey' => 0x0B,
			'ClientId' => 0x0C,
			'ServerId' => 0x0D,
			'Status' => 0x0E,
			'Collection' => 0x0F,
			'Class' => 0x10,
			'CollectionId' => 0x12,
			'GetChanges' => 0x13,
			'MoreAvailable' => 0x14,
			'WindowSize' => 0x15,
			'Commands' => 0x16,
			'Options' => 0x17,
			'FilterType' => 0x18,
			'Truncation' => 0x19,
			'Conflict' => 0x1B,
			'Collections' => 0x1C,
			'Data' => 0x1D,
			'DeletesAsMoves' => 0x1E,
			'Supported' => 0x20,
			'SoftDelete' => 0x21,
			'MIMESupport' => 0x22,
			'MIMETruncation' => 0x23,
			'Wait' => 0x24,
			'Limit' => 0x25,
			'Partial' => 0x26,
			// EAS 14.0
			'ConversationMode' => 0x27,
			'MaxItems' => 0x28,
			'HeartbeatInterval' => 0x29,
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
			'GetItemEstimate' => 0x05,
			'Version' => 0x06,    // 12.1
			'Collections' => 0x07,
			'Collection' => 0x08,
			'CollectionClass' => 0x09, // 12.1
			'CollectionId' => 0x0A,
			'DateTime' => 0x0B,   // 12.1
			'Estimate' => 0x0C,
			'Response' => 0x0D,
			'Status' => 0x0E,
		],
		// #7 FolderHierarchy
		0x07 => [
			'Collections' => 0x05,
			'Collection' => 0x06,
			'Name' => 0x07,
			'Id' => 0x08,
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
			'BodyPreference' => 0x05,
			'Type' => 0x06,
			'TruncationSize' => 0x07,
			'AllOrNone' => 0x08,
			'Body' => 0x09,
			'Data' => 0x0B,
			'EstimatedDataSize' => 0x0C,
			'Truncated' => 0x0D,
			'Attachments' => 0x0E,
			'Attachment' => 0x0F,
			'DisplayName' => 0x10,
			'FileReference' => 0x11,
			'Method' => 0x12,
			'ContentId' => 0x13,
			'ContentLocation' => 0x14,
			'IsInline' => 0x15,
			'NativeBodyType' => 0x16,
			'ContentType' => 0x17,
			// EAS 14.0
			'Preview' => 0x18,
			// EAS 14.1
			'BodyPartPreference' => 0x19,
			'BodyPart' => 0x1A,
			'Status' => 0x1B,
			// EAS 16.0
			'Add' => 0x1C,
			'Delete' => 0x1D,
			'ClientId' => 0x1E,
			'Content' => 0x1F,
			'Location' => 0x20,
			'Annontation' => 0x21,
			'Street' => 0x22,
			'City' => 0x23,
			'State' => 0x24,
			'Country' => 0x25,
			'PostalCode' => 0x26,
			'Latitude' => 0x27,
			'Longitude' => 0x28,
			'Accuracy' => 0x29,
			'Altitude' => 0x2A,
			'AltitudeAccuracy' => 0x2B,
			'LocationUri' => 0x2C,
			'InstanceId' => 0x2D,
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
			'Subject' => 0x05,
			'MessageClass' => 0x06,
			'LastModifiedDate' => 0x07,
			'Categories' => 0x08,
			'Category' => 0x09,
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
		// #25 Find
		0x19 => [
			'Find' => 0x05,
			'SearchId' => 0x06,
			'ExecuteSearch' => 0x07,
			'MailBoxSearchCriterion' => 0x08,
			'Query' => 0x09,
			'Status' => 0x0A,
			'FreeText' => 0x0B,
			'Options' => 0x0C,
			'Range' => 0x0D,
			'DeepTraversal' => 0x0E,
			'Response' => 0x11,
			'Result' => 0x12,
			'Properties' => 0x13,
			'Preview' => 0x14,
			'HasAttachments' => 0x15
		],
		// #254 Windows Live
		0xFE => [
			'Annotations' => 0x05,
			'Annotation' => 0x06,
			'Name' => 0x07,
			'Value' => 0x08
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
	 * convert object to wbxml binary
	 * 
	 * @param $stream	output stream
	 * @param $object	object to convert stream
	 * 
	 */
	public function streamFromObject($stream, $object): void {

		// write header
		$this->_writeByte($stream, EasXml::VERSION_V13);
        $this->_writeMBUInt($stream, EasXml::IDENTIFIER);
        $this->_writeMBUInt($stream, EasXml::ENCODING);
        $this->_writeMBUInt($stream, 0);
		// write body
		$page = 0;
		$this->_writeBodyFromObject($stream, $object, $page);

	}

	/**
     * write eas object as wbxml binary
     *
	 * @param $stream	output stream
	 * @param $object	object to convert
	 * @param $page		namespace code page
	 * 
     */
    private function _writeBodyFromObject($stream, $object, &$page): void {

		foreach (get_object_vars($object) as $token => $property) {

			/**
			 * TODO: Handel arrays
			 */
			
			$namespace = $property->getNamespace();
			
			if ($page !== self::$_namespaces[$namespace]) {
				$page = self::$_namespaces[$namespace];
				$this->_writeByte($stream, EasXml::CODESPACE);
				$this->_writeByte($stream, $page);
			}

			if ($property instanceof EasObject) {
				
				// write node start
        		$this->_writeNodeStart($stream, $page, $token, true, false);
				// write node contents
				$this->_writeBodyFromObject($stream, $property, $page);
				// write node end
				$this->_writeNodeEnd($stream);

			}
			elseif ($property instanceof EasProperty) {

				if ($property->hasContents()) {
					// write node start
					$this->_writeNodeStart($stream, $page, $token, true, false);
					if ($property->getOpaque()) {
						// write node contents
						$this->_writeData($stream, $property->getContents());
					}
					else {
						// write node contents
						$this->_writeString($stream, $property->getContents());
					}
				}
				else {
					// write node start
					$this->_writeNodeStart($stream, $page, $token, false, false);
				}
				// write node end
				$this->_writeNodeEnd($stream);

			}
		}

	}

	/**
     * write node start as wbxml binary
     *
	 * @param $stream		output stream
	 * @param $space		codespace
	 * @param $name			named token
	 * @param $contents		contents flag
	 * @param $attributes	attributes flag
	 * 
     */
    private function _writeNodeStart($stream, int $space, string $name, bool $contents = false, bool $attributes = false): void {
		
		// convert named token to token code
		$code = self::$_codes[$space][$name];
		// evaluate, if code was found
		if (!isset($code)) {
			throw new \UnexpectedValueException("Unknown token encountered $space:$name");
		}
		// alter code depenting on flags
		if ($attributes) {
            $code |= EasXml::NODE_CONTENTS;
        } elseif ($contents) {
            $code |= EasXml::NODE_CONTENTS;
        }
		// write node start token to stream
		$this->_writeByte($stream, $code);

    }

	/**
     * write byte as wbxml binary
     *
	 * @param $stream	output stream
	 * @param $byte		byte to write
	 * 
     */
    private function _writeNodeEnd($stream, ): void {

		// write node end token to stream
		$this->_writeByte($stream, EasXml::NODE_END);

    }

	/**
     * write byte as wbxml binary
     *
	 * @param $stream	output stream
	 * @param $byte		byte to write
	 * 
     */
    private function _writeByte($stream, $byte): void {
		// write byte to stream
		fwrite($stream, chr($byte));
    }

    /**
     * write integer as wbxml multibyte binary
     *
	 * @param $stream	output stream
	 * @param $int		int to write
	 * 
     */
    private function _writeMBUInt($stream, $int): void {

        while (1) {
            $byte = $int & 0x7f;
            $int = $int >> 7;
            if ($int == 0) {
                $this->_writeByte($stream, $byte);
                break;
            } else {
                $this->_writeByte($stream, $byte | 0x80);
            }
        }

    }

	/**
     * write string as wbxml binary
     *
	 * @param $stream	output stream
	 * @param $content	content to write
	 * 
     */
    private function _writeString($stream, $content): void {

		// replace any termination character in string
		$content = str_replace("\0", '', $content);
		// write string start token
		$this->_writeByte($stream, EasXml::STRING_INLINE);
		// write string
        fwrite($stream, $content);
		// write string end token
    	$this->_writeByte($stream, 0);

    }

	/**
     * write opeque data as wbxml binary
     *
	 * @param $stream	output stream
	 * @param $content	data to write
	 * 
     */
    private function _writeData($stream, $content): void {

		// write data start token
		$this->_writeByte($stream, EasXml::DATA);
		// write data lenght
		$this->_writeMBUInt($stream, strlen($content));
		// write data
        fwrite($stream, $content);

    }

}