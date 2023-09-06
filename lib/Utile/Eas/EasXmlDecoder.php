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

class EasXmlDecoder{

	//https://learn.microsoft.com/en-us/previous-versions/office/developer/exchange-server-interoperability-guidance/hh361570(v=exchg.140)

	/**
	 * TODO: Add decode to xml
	 */

	/**
     * namespace definitions from wbxml codes
     *
     * @var array
     */
	private static $_namespaces = [
		0x00 => 'AirSync',
		0x01 => 'Contacts',
		0x02 => 'Email',
		0x04 => 'Calendar',
		0x05 => 'Move',
		0x06 => 'GetItemEstimate',
		0x07 => 'FolderHierarchy',
		0x08 => 'MeetingResponse',
		0x09 => 'Tasks',
		0x0A => 'ResolveRecipients',
		0x0B => 'ValidateCert',
		0x0C => 'Contacts2',
		0x0D => 'Ping',
		0x0E => 'Provision',
		0x0F => 'Search',
		0x10 => 'Gal',
		0x11 => 'AirSyncBase',
		0x12 => 'Settings',
		0x13 => 'DocumentLibrary',
		0x14 => 'ItemOperations',
		0x15 => 'ComposeMail',
		0x16 => 'Email2',
		0x17 => 'Notes',
		0x18 => 'RightsManagement',
		0x19 => 'Find',
		0xFE => 'WindowsLive'
	];

	/**
     * property definitions from wbxml codes
     *
     * @var array
     */
    private static $_codes = [
		// #0 AirSync
		0x00 => [
			0x05 => 'Sync',
			0x06 => 'Responses',
			0x07 => 'Add',
			0x08 => 'Modify',
			0x09 => 'Delete',
			0x0A => 'Fetch',
			0x0B => 'SyncKey',
			0x0C => 'ClientId',
			0x0D => 'ServerId',
			0x0E => 'Status',
			0x0F => 'Collection',
			0x10 => 'Class',
			0x12 => 'CollectionId',
			0x13 => 'GetChanges',
			0x14 => 'MoreAvailable',
			0x15 => 'WindowSize',
			0x16 => 'Commands',
			0x17 => 'Options',
			0x18 => 'FilterType',
			0x19 => 'Truncation',
			0x1B => 'Conflict',
			0x1C => 'Collections',
			0x1D => 'Data',
			0x1E => 'DeletesAsMoves',
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
			0x0A => 'BodySize',
			0x0B => 'BodyTruncated',
			0x0C => 'Business2PhoneNumber',
			0x0D => 'BusinessCity',
			0x0E => 'BusinessCountry',
			0x0F => 'BusinessPostalCode',
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
			0x1A => 'Department',
			0x1B => 'Email1Address',
			0x1C => 'Email2Address',
			0x1D => 'Email3Address',
			0x1E => 'FileAs',
			0x1F => 'FirstName',
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
			0x2A => 'MiddleName',
			0x2B => 'MobilePhoneNumber',
			0x2C => 'OfficeLocation',
			0x2D => 'OtherCity',
			0x2E => 'OtherCountry',
			0x2F => 'OtherPostalCode',
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
			0x3A => 'YomiLastName',
			0x3B => 'Rtf',               // EAS 2.5 only.
			0x3C => 'Picture',
			// EAS 14.0
			0x3D => 'Alias',
			0x3E => 'WeightedRank',
		],
		// #2 Email
		0x02 => [
			0x05 => 'Attachment',
			0x06 => 'Attachments',
			0x07 => 'AttName',
			0x08 => 'AttSize',
			0x09 => 'AttOid',
			0x0A => 'AttMethod',
			0x0B => 'AttRemoved',
			0x0C => 'Body',
			0x0D => 'BodySize',
			0x0E => 'BodyTruncated',
			0x0F => 'DateReceived',
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
			0x1A => 'AllDayEvent',
			0x1B => 'Categories',  // EAS 14.0
			0x1C => 'Category',    // EAS 14.0
			0x1D => 'DtStamp',
			0x1E => 'EndTime',
			0x1F => 'InstanceType',
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
			0x2A => 'Until',
			0x2B => 'Occurrences',
			0x2C => 'Interval',
			0x2D => 'DayOfWeek',
			0x2E => 'DayOfMonth',
			0x2F => 'WeekOfMonth',
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
			0x3A => 'Flag',
			0x3B => 'FlagStatus',
			0x3C => 'ContentClass',
			0x3D => 'FlagType',
			0x3E => 'CompleteTime',
			// EAS 14.0
			0x3F => 'DisallowNewTimeProposal',
		],
		// #3 AirNotify is deprecated
		// #4 Calendar
		0x04 => [
			0x05 => 'Timezone',
			0x06 => 'AllDayEvent',
			0x07 => 'Attendees',
			0x08 => 'Attendee',
			0x09 => 'Email',
			0x0A => 'Name',
			0x0B => 'Body',          // 2.5 Only
			0x0C => 'BodyTruncated', // 2.5 Only
			0x0D => 'BusyStatus',
			0x0E => 'Categories',
			0x0F => 'Category',
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
			0x1A => 'OrganizerName',
			0x1B => 'Recurrence',
			0x1C => 'Type',
			0x1D => 'Until',
			0x1E => 'Occurrences',
			0x1F => 'Interval',
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
			0x3A => 'OnlineMeetingConfLink',
			0x3B => 'OnlineMeetingExternalLink',
			// EAS 16.0
			0x3C => 'ClientUid',
		],
		// #5 Move
		0x05 => [
			0x05 => 'Moves',
			0x06 => 'Move',
			0x07 => 'SrcMsgId',
			0x08 => 'SrcFldId',
			0x09 => 'DstFldId',
			0x0A => 'Response',
			0x0B => 'Status',
			0x0C => 'DstMsgId',
		],
		// #6 GetItemEstimate
		0x06 => [
			0x05 => 'GetItemEstimate',
			0x06 => 'Version',    // 12.1
			0x07 => 'Folders',
			0x08 => 'Folder',
			0x09 => 'FolderType', // 12.1
			0x0A => 'FolderId',
			0x0B => 'DateTime',   // 12.1
			0x0C => 'Estimate',
			0x0D => 'Response',
			0x0E => 'Status',
		],
		// #7 FolderHierarchy
		0x07 => [
			0x05 => 'Collections',
			0x06 => 'Collection',
			0x07 => 'Name',
			0x08 => 'Id',
			0x09 => 'ParentId',
			0x0A => 'Type',
			0x0B => 'Response',
			0x0C => 'Status',
			0x0D => 'ContentClass',
			0x0E => 'Changes',
			0x0F => 'Add',
			0x10 => 'Remove',
			0x11 => 'Update',
			0x12 => 'SyncKey',
			0x13 => 'FolderCreate',
			0x14 => 'FolderDelete',
			0x15 => 'FolderUpdate',
			0x16 => 'FolderSync',
			0x17 => 'Count',
			0x18 => 'Version',
		],
		// #8 MeetingResponse
		0x08 => [
			0x05 => 'CalendarId',
			0x06 => 'FolderId',
			0x07 => 'MeetingResponse',
			0x08 => 'RequestId',
			0x09 => 'Request',
			0x0A => 'Result',
			0x0B => 'Status',
			0x0C => 'UserResponse',
			0x0D => 'Version',
			// EAS 14.1
			0x0E => 'InstanceId',
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
			0x0A => 'Complete',
			0x0B => 'DateCompleted',
			0x0C => 'DueDate',
			0x0D => 'UtcDueDate',
			0x0E => 'Importance',
			0x0F => 'Recurrence',
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
			0x1A => 'DeadOccur',
			0x1B => 'ReminderSet',
			0x1C => 'ReminderTime',
			0x1D => 'Sensitivity',
			0x1E => 'StartDate',
			0x1F => 'UtcStartDate',
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
			0x0A => 'DisplayName',
			0x0B => 'EmailAddress',
			0x0C => 'Certificates',
			0x0D => 'Certificate',
			0x0E => 'MiniCertificate',
			0x0F => 'Options',
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
			0x1A => 'Picture',
			0x1B => 'MaxSize',
			0x1C => 'Data',
			0x1D => 'MaxPictures',
		],
		// #11 ValidateCert
		0x0B => [
			0x05 => 'ValidateCert',
			0x06 => 'Certificates',
			0x07 => 'Certificate',
			0x08 => 'CertificateChain',
			0x09 => 'CheckCRL',
			0x0A => 'Status',
		],
		// #12 Contacts2
		0x0C => [
			0x05 => 'CustomerId',
			0x06 => 'GovernmentId',
			0x07 => 'IMAddress',
			0x08 => 'IMAddress2',
			0x09 => 'IMAddress3',
			0x0A => 'ManagerName',
			0x0B => 'CompanyMainPhone',
			0x0C => 'AccountName',
			0x0D => 'NickName',
			0x0E => 'MMS',
		],
		// #13 Ping
		0x0D => [
			0x05 => 'Ping',
			0x06 => 'AutdState',
			0x07 => 'Status',
			0x08 => 'HeartbeatInterval',
			0x09 => 'Folders',
			0x0A => 'Folder',
			0x0B => 'ServerEntryId',
			0x0C => 'FolderType',
			0x0D => 'MaxFolders',
		],
		// #14 Provision
		0x0E => [
			0x05 => 'Provision',
			0x06 => 'Policies',
			0x07 => 'Policy',
			0x08 => 'PolicyType',
			0x09 => 'PolicyKey',
			0x0A => 'Data',
			0x0B => 'Status',
			0x0C => 'RemoteWipe',
			0x0D => 'EASProvisionDoc',
			// EAS 12.0
			0x0E => 'DevicePasswordEnabled',
			0x0F => 'AlphanumericDevicePasswordRequired',
			0x10 => 'DeviceEncryptionEnabled',
			0x11 => 'PasswordRecoveryEnabled',
			0x12 => 'DocumentBrowseEnabled',
			0x13 => 'AttachmentsEnabled',
			0x14 => 'MinDevicePasswordLength',
			0x15 => 'MaxInactivityTimeDeviceLock',
			0x16 => 'MaxDevicePasswordFailedAttempts',
			0x17 => 'MaxAttachmentSize',
			0x18 => 'AllowSimpleDevicePassword',
			0x19 => 'DevicePasswordExpiration',
			0x1A => 'DevicePasswordHistory',
			// EAS 12.1
			0x1B => 'AllowStorageCard',
			0x1C => 'AllowCamera',
			0x1D => 'RequireDeviceEncryption',
			0x1E => 'AllowUnsignedApplications',
			0x1F => 'AllowUnsignedInstallationPackages',
			0x20 => 'MinDevicePasswordComplexCharacters',
			0x21 => 'AllowWiFi',
			0x22 => 'AllowTextMessaging',
			0x23 => 'AllowPOPIMAPEmail',
			0x24 => 'AllowBluetooth',
			0x25 => 'AllowIrDA',
			0x26 => 'RequireManualSyncWhenRoaming',
			0x27 => 'AllowDesktopSync',
			0x28 => 'MaxCalendarAgeFilter',
			0x29 => 'AllowHTMLEmail',
			0x2A => 'MaxEmailAgeFilter',
			0x2B => 'MaxEmailBodyTruncationSize',
			0x2C => 'MaxHTMLBodyTruncationSize',
			0x2D => 'RequireSignedSMIMEMessages',
			0x2E => 'RequireEncryptedSMIMEMessages',
			0x2F => 'RequireSignedSMIMEAlgorithm',
			0x30 => 'RequireEncryptedSMIMEAlgorithm',
			0x31 => 'AllowSMIMEEncryptionAlgorithmNegotiation',
			0x32 => 'AllowSMIMESoftCerts',
			0x33 => 'AllowBrowser',
			0x34 => 'AllowConsumerEmail',
			0x35 => 'AllowRemoteDesktop',
			0x36 => 'AllowInternetSharing',
			0x37 => 'UnapprovedInROMApplicationList',
			0x38 => 'ApplicationName',
			0x39 => 'ApprovedApplicationList',
			0x3A => 'Hash',
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
			0x1A => 'BodyPart',
			0x1B => 'Status',
			// EAS 16.0
			0x1C => 'Add',
			0x1D => 'Delete',
			0x1E => 'ClientId',
			0x1F => 'Content',
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
			0x2A => 'Altitude',
			0x2B => 'AltitudeAccuracy',
			0x2C => 'LocationUri',
			0x2D => 'InstanceId',
		],
		// #18 Settings
		0x12 => [
			0x05 => 'Settings',
			0x06 => 'Status',
			0x07 => 'Get',
			0x08 => 'Set',
			0x09 => 'Oof',
			0x0A => 'OofState',
			0x0B => 'StartTime',
			0x0C => 'EndTime',
			0x0D => 'OofMessage',
			0x0E => 'AppliesToInternal',
			0x0F => 'AppliesToExternalKnown',
			0x10 => 'AppliesToExternalUnknown',
			0x11 => 'Enabled',
			0x12 => 'ReplyMessage',
			0x13 => 'BodyType',
			0x14 => 'DevicePassword',
			0x15 => 'Password',
			0x16 => 'DeviceInformation',
			0x17 => 'Model',
			0x18 => 'IMEI',
			0x19 => 'FriendlyName',
			0x1A => 'OS',
			0x1B => 'OSLanguage',
			0x1C => 'PhoneNumber',
			0x1D => 'UserInformation',
			0x1E => 'EmailAddresses',
			0x1F => 'SmtpAddress',
			// EAS 12.1
			0x20 => 'UserAgent',
			// EAS 14.0
			0x21 => 'EnableOutboundSMS',
			0x22 => 'MobileOperator',
			// EAS 14.1
			0x23 => 'PrimarySmtpAddress',
			0x24 => 'Accounts',
			0x25 => 'Account',
			0x26 => 'AccountId',
			0x27 => 'AccountName',
			0x28 => 'UserDisplayName',
			0x29 => 'SendDisabled',
			0x2B => 'RightsManagementInformation',
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
		// #25 Find
		0x19 => [
			0x05 => 'Find',
			0x06 => 'SearchId',
			0x07 => 'ExecuteSearch',
			0x08 => 'MailBoxSearchCriterion',
			0x09 => 'Query',
			0x0A => 'Status',
			0x0B => 'FreeText',
			0x0C => 'Options',
			0x0D => 'Range',
			0x0E => 'DeepTraversal',
			0x11 => 'Response',
			0x12 => 'Result',
			0x13 => 'Properties',
			0x14 => 'Preview',
			0x15 => 'HasAttachments'
		],
		// #254 Windows Live
		0xFE => [
			0x05 => 'Annotations',
			0x06 => 'Annotation',
			0x07 => 'Name',
			0x08 => 'Value'
		]
	];

	/**
	 * WBXMLDecoder constructor.
	 */
	public function __construct() {

	}

	/**
	 * convert string data to object
	 * 
	 * @param $input
	 * 
	 * @throws Throwable
	 */
	public function stringToObject($data): object{

		// construct stream object
		$stream = fopen('php://temp', 'rw');
		try {
			// write data to stream
			fwrite($stream, $data);
			// rewind stream to start
			rewind($stream);
			// process data
			return $this->streamToObject($stream);
			// close stream
			fclose($stream);
		} catch (\Throwable $e) {
			// close stream
			fclose($stream);
			// throw error
			throw $e;
		}

	}

	/**
	 * convert stream data to object
	 * 
	 * @param $stream	data stream
	 * 
	 * @throws Exception
	 */
	public function streamToObject($stream): object{

		// construct base object
		$o = new \stdClass();
		// version
		$version = $this->_readByte($stream);
		// evaluate, version
		if($version !== EasXml::VERSION_V10 && $version !== EasXml::VERSION_V11 && $version !== EasXml::VERSION_V12 && $version !== EasXml::VERSION_V13){
			throw new \UnexpectedValueException("WBXML version 0x$version not supported");
		}
		// idenitifier
		$identifier = $this->_readMBUInt($stream);
		// evaluate, public identifier
		if ($identifier != EasXml::IDENTIFIER) {
			throw new \UnexpectedValueException("WBXML public idenitifier 0x$identifier not supported");
		}
		// encoding
		$encoding = $this->_readMBUInt($stream);
		// evaluate, character encoding
		if ($encoding != EasXml::ENCODING) {
			throw new \UnexpectedValueException("WBXML character encoding 0x$encoding not supported");
		}
		// evaluate, strings table size
		if ($this->_readMBUInt($stream) > 0) {
			throw new \UnexpectedValueException("WBXML strings tables not supported");
		}

		// construct message property
		$o = $this->_readBodyToObject($stream, 0);

		return $o;

	}

	/**
     * read body from stream and convert to object
     *
     * @return string  The single byte.
     */
    protected function _readBodyToObject($stream, $page): mixed {
		// construct object place holder
		$node = new EasObject();

		while(!feof($stream)) {
			
			$byte = $this->_readByte($stream);

			switch($byte) {
				case EasXml::CODESPACE:
					$page = $this->_readByte($stream);
					if ($page < 0 || $page > 25)
					{
						throw new \UnexpectedValueException("Unknown code page ID 0x$page encountered in WBXML.");
					}
					break;
				case EasXml::NODE_END:
					$token = 'END';
					break 2;
				case EasXml::STRING_INLINE:
					$node = $this->_readString($stream);
					$type = 'S';
					break;
				case EasXml::DATA:
					$node = $this->_readData($stream);
					$type = 'O';
					break;
				// According to MS-ASWBXML, these features aren't used
				case EasXml::ENTITY:
				case EasXml::EXT_0:
				case EasXml::EXT_1:
				case EasXml::EXT_2:
				case EasXml::EXT_I_0:
				case EasXml::EXT_I_1:
				case EasXml::EXT_I_2:
				case EasXml::EXT_T_0:
				case EasXml::EXT_T_1:
				case EasXml::EXT_T_2:
				case EasXml::LITERAL:
				case EasXml::LITERAL_A:
				case EasXml::LITERAL_AC:
				case EasXml::LITERAL_C:
				case EasXml::PI:
				case EasXml::STR_T:
					throw new \UnexpectedValueException("Unknown global token 0x$byte.");
				// If it's not a global token, it should be a tag
				default:
					$hasAttributes = false;
					$hasContents = false;

					$hasAttributes = ($byte & EasXml::NODE_ATTRIBUTES) > 0;
					$hasContents = ($byte & EasXml::NODE_CONTENTS) > 0;

					$code = ($byte & 0x3F);

					// evalute, if token has attributes
					if ($hasAttributes) {
						throw new \UnexpectedValueException("Token 0x$code has attributes.");
					}

					// evaluate, if namespace is valid
					if (isset(self::$_namespaces[$page])) {
						$namespace = self::$_namespaces[$page];
					}
					else {
						throw new \UnexpectedValueException("Namespace 0x$page is not valid.");
					}
					// evaluate, if token is valid
					if (isset(self::$_codes[$page][$code])) {
						$token = self::$_codes[$page][$code];
					}
					else {
						$token = "UNKNOWN_TAG_$token";
					}
					$node->setNamespace($namespace);

					if ($hasContents) {
						// read next object
						$o = $this->_readBodyToObject($stream, $page);
						// evaluate if object or property already exisits and is not an array
						if (isset($node->{$token}) && !is_array($node->{$token})) {
							// convert tag object or property to array
							$node->{$token} = [clone $node->{$token}];
						}
						elseif (!isset($node->{$token})) {
							// construct object or property place holder from tag
							$node->{$token} = null;
						}

						if (is_array($node->{$token})) {
							// add object as property
							if ($o instanceof EasObject) {
								$node->{$token}[] = $o;
							} else {
								$node->{$token}[] = new EasProperty($namespace, $o);
							}
						} else {
							// add object as property
							if ($o instanceof EasObject) {
								$node->{$token} = $o;
							} else {
								$node->{$token} = new EasProperty($namespace, $o);
							}
						}
					}
				break;
			}
		}

		return $node;

    }

	/**
     * read a single byte from the stream.
     *
     * @return string  The single byte.
     */
    protected function _readByte($stream): mixed {
		$byte = fread($stream,1);
        if (strlen($byte) > 0) {
            return ord($byte);
        } else {
            return null;
        }
    }

    /**
     * read a MBU integer from the stream
     *
     * @return integer
     */
    protected function _readMBUInt($stream): mixed {
        $uint = 0;
        while (1) {
          $byte = $this->_readByte($stream);
          $uint |= $byte & 0x7f;
          if ($byte & 0x80) {
              $uint = $uint << 7;
          } else {
              break;
          }
        }
        return $uint;
    }

	/**
     * read a null terminated string from the stream
     *
     * @return string  The string
     */
    protected function _readString($stream): ?string {
        $contents = '';
        while(1) {
            $in = $this->_readByte($stream);
            if ($in == 0) {
                break;
            } else {
                $contents .= chr($in);
            }
        }
        return $contents;
    }

	/**
     * read an opaque value from the stream
     *
     * @return string  A string of bytes representing the opaque value.
     */
	protected function _readData($stream): ?string {
        // See http://php.net/fread for why we can't simply use a single fread()
        // here. Bottom line, for buffered network streams it is possible
        // that fread will only return a portion of the stream if chunk
        // is smaller then $size, so we use a loop to reach $size.

		// read string size
		$size = $this->_readMBUInt($stream);
        $contents = '';
        while (1) {
            $length = (($size - strlen($contents)) > 8192) ? 8192 : ($size - strlen($contents));
            if ($length > 0) {
                $chunk = fread($stream, $length);
                // evaluate if read failed or stream ended
                if ($chunk === false || feof($stream)) {
                    throw new \Exception(sprintf(
                        'Stream unavailable while trying to read %d bytes from stream. Aborting after %d bytes read.',
                        $size,
                        strlen($contents)));
                } else {
                    $contents .= $chunk;
                }
            }
            if (strlen($contents) >= $size) {
                break;
            }
        }
        return $contents;
    }

}