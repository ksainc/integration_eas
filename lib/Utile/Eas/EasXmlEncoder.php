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
			'EntityId' => 0x0D,
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
			'Anniversary' => 0x05,
			'AssistantName' => 0x06,
			'AssistnamePhoneNumber' => 0x07,
			'Birthday' => 0x08,
			'Body' => 0x09,
			'BodySize' => 0x0A,
			'BodyTruncated' => 0x0B,
			'Business2PhoneNumber' => 0x0C,
			'BusinessCity' => 0x0D,
			'BusinessCountry' => 0x0E,
			'BusinessPostalCode' => 0x0F,
			'BusinessState' => 0x10,
			'BusinessStreet' => 0x11,
			'BusinessFaxNumber' => 0x12,
			'BusinessPhoneNumber' => 0x13,
			'CarPhoneNumber' => 0x14,
			'Categories' => 0x15,
			'Category' => 0x16,
			'Children' => 0x17,
			'Child' => 0x18,
			'CompanyName' => 0x19,
			'Department' => 0x1A,
			'Email1Address' => 0x1B,
			'Email2Address' => 0x1C,
			'Email3Address' => 0x1D,
			'FileAs' => 0x1E,
			'FirstName' => 0x1F,
			'Home2PhoneNumber' => 0x20,
			'HomeCity' => 0x21,
			'HomeCountry' => 0x22,
			'HomePostalCode' => 0x23,
			'HomeState' => 0x24,
			'HomeStreet' => 0x25,
			'HomeFaxNumber' => 0x26,
			'HomePhoneNumber' => 0x27,
			'JobTitle' => 0x28,
			'LastName' => 0x29,
			'MiddleName' => 0x2A,
			'MobilePhoneNumber' => 0x2B,
			'OfficeLocation' => 0x2C,
			'OtherCity' => 0x2D,
			'OtherCountry' => 0x2E,
			'OtherPostalCode' => 0x2F,
			'OtherState' => 0x30,
			'OtherStreet' => 0x31,
			'PagerNumber' => 0x32,
			'RadioPhoneNumber' => 0x33,
			'Spouse' => 0x34,
			'Suffix' => 0x35,
			'Title' => 0x36,
			'WebPage' => 0x37,
			'YomiCompanyName' => 0x38,
			'YomiFirstName' => 0x39,
			'YomiLastName' => 0x3A,
			'Rtf' => 0x3B,               // EAS 2.5 only.
			'Picture' => 0x3C,
			// EAS 14.0
			'Alias' => 0x3D,
			'WeightedRank' => 0x3E
		],
		// #2 Email
		0x02 => [
			'Attachment' => 0x05,
			'Attachments' => 0x06,
			'AttName' => 0x07,
			'AttSize' => 0x08,
			'AttOid' => 0x09,
			'AttMethod' => 0x0A,
			'AttRemoved' => 0x0B,
			'Body' => 0x0C,
			'BodySize' => 0x0D,
			'BodyTruncated' => 0x0E,
			'DateReceived' => 0x0F,
			'DisplayName' => 0x10,
			'DisplayTo' => 0x11,
			'Importance' => 0x12,
			'MessageClass' => 0x13,
			'Subject' => 0x14,
			'Read' => 0x15,
			'To' => 0x16,
			'Cc' => 0x17,
			'From' => 0x18,
			'Reply-To' => 0x19,
			'AllDayEvent' => 0x1A,
			'Categories' => 0x1B,  // EAS 14.0
			'Category' => 0x1C,    // EAS 14.0
			'DtStamp' => 0x1D,
			'EndTime' => 0x1E,
			'InstanceType' => 0x1F,
			'BusyStatus' => 0x20,
			'Location' => 0x21,
			'MeetingRequest' => 0x22,
			'Organizer' => 0x23,
			'RecurrenceId' => 0x24,
			'Reminder' => 0x25,
			'ResponseRequested' => 0x26,
			'Recurrences' => 0x27,
			'Recurrence' => 0x28,
			'Type' => 0x29,
			'Until' => 0x2A,
			'Occurrences' => 0x2B,
			'Interval' => 0x2C,
			'DayOfWeek' => 0x2D,
			'DayOfMonth' => 0x2E,
			'WeekOfMonth' => 0x2F,
			'MonthOfYear' => 0x30,
			'StartTime' => 0x31,
			'Sensitivity' => 0x32,
			'TimeZone' => 0x33,
			'GlobalObjId' => 0x34,
			'ThreadTopic' => 0x35,
			'MIMEData' => 0x36,
			'MIMETruncated' => 0x37,
			'MIMESize' => 0x38,
			'InternetCPID' => 0x39,
			// EAS 12.0
			'Flag' => 0x3A,
			'FlagStatus' => 0x3B,
			'ContentClass' => 0x3C,
			'FlagType' => 0x3D,
			'CompleteTime' => 0x3E,
			// EAS 14.0
			'DisallowNewTimeProposal' => 0xF
		],
		// #3 AirNotify is deprecated
		// #4 Calendar
		0x04 => [
			'Timezone' => 0x05,
			'AllDayEvent' => 0x06,
			'Attendees' => 0x07,
			'Attendee' => 0x08,
			'Email' => 0x09,
			'Name' => 0x0A,
			'Body' => 0x0B,          // 2.5 Only
			'BodyTruncated' => 0x0C, // 2.5 Only
			'BusyStatus' => 0x0D,
			'Categories' => 0x0E,
			'Category' => 0x0F,
			'Rtf' => 0x10,           // 2.5 ONly
			'DtStamp' => 0x11,
			'EndTime' => 0x12,
			'Exception' => 0x13,
			'Exceptions' => 0x14,
			'Deleted' => 0x15,
			'ExceptionStartTime' => 0x16,
			'Location' => 0x17,
			'MeetingStatus' => 0x18,
			'OrganizerEmail' => 0x19,
			'OrganizerName' => 0x1A,
			'Recurrence' => 0x1B,
			'Type' => 0x1C,
			'Until' => 0x1D,
			'Occurrences' => 0x1E,
			'Interval' => 0x1F,
			'DayOfWeek' => 0x20,
			'DayOfMonth' => 0x21,
			'WeekOfMonth' => 0x22,
			'MonthOfYear' => 0x23,
			'Reminder' => 0x24,
			'Sensitivity' => 0x25,
			'Subject' => 0x26,
			'StartTime' => 0x27,
			'UID' => 0x28,
			// EAS 12.0
			'AttendeeStatus' => 0x29,
			'AttendeeType' => 0x2A,
			// EAS 12.1 (Apparently no longer documented).
			'Attachment' => 0x2B,
			'Attachments' => 0x2C,
			'AttName' => 0x2D,
			'AttSize' => 0x2E,
			'AttOid' => 0x2F,
			'AttMethod' => 0x30,
			'AttRemoved' => 0x31,
			'DisplayName' => 0x32,
			// EAS 14
			'DisallowNewTimeProposal' => 0x33,
			'ResponseRequested' => 0x34,
			'AppointmentReplyTime' => 0x35,
			'ResponseType' => 0x36,
			'CalendarType' => 0x37,
			'IsLeapMonth' => 0x38,
			// EAS 14.1
			'FirstDayOfWeek' => 0x39,
			'OnlineMeetingConfLink' => 0x3A,
			'OnlineMeetingExternalLink' => 0x3B,
			// EAS 16.0
			'ClientUid' => 0x3C
		],
		// #5 Move
		0x05 => [
			'Moves' => 0x05,
			'Move' => 0x06,
			'SrcMsgId' => 0x07,
			'SrcFldId' => 0x08,
			'DstFldId' => 0x09,
			'Response' => 0x0A,
			'Status' => 0x0B,
			'DstMsgId' => 0x0C,
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
			'Type' => 0x0A,
			'Response' => 0x0B,
			'Status' => 0x0C,
			'ContentClass' => 0x0D,
			'Changes' => 0x0E,
			'Add' => 0x0F,
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
			'CalendarId' => 0x05,
			'CollectionId' => 0x06,
			'MeetingResponse' => 0x07,
			'RequestId' => 0x08,
			'Request' => 0x09,
			'Result' => 0x0A,
			'Status' => 0x0B,
			'UserResponse' => 0x0C,
			'Version' => 0x0D,
			'InstanceId' => 0x0E, //EAS 14.1
			'ProposedStartTime' => 0x10, // EAS 16.1
			'ProposedEndTime' => 0x11, // EAS 16.1
			'SendResponse' => 0x12, // EAS 16.0
		],
		// #9 Tasks
		0x09 => [
			'Body' => 0x05,
			'BodySize' => 0x06,
			'BodyTruncated' => 0x07,
			'Categories' => 0x08,
			'Category' => 0x09,
			'Complete' => 0x0A,
			'DateCompleted' => 0x0B,
			'DueDate' => 0x0C,
			'UtcDueDate' => 0x0D,
			'Importance' => 0x0E,
			'Recurrence' => 0x0F,
			'Type' => 0x10,
			'Start' => 0x11,
			'Until' => 0x12,
			'Occurrences' => 0x13,
			'Interval' => 0x14,
			'DayOfMonth' => 0x15,
			'DayOfWeek' => 0x16,
			'WeekOfMonth' => 0x17,
			'MonthOfYear' => 0x18,
			'Regenerate' => 0x19,
			'DeadOccur' => 0x1A,
			'ReminderSet' => 0x1B,
			'ReminderTime' => 0x1C,
			'Sensitivity' => 0x1D,
			'StartDate' => 0x1E,
			'UtcStartDate' => 0x1F,
			'Subject' => 0x20,
			'Rtf' => 0x21,
			'OrdinalDate' => 0x22, // EAS 12.0
			'SubOrdinalDate' => 0x23, // EAS 12.0
			'CalendarType' => 0x24, // EAS 14.0
			'IsLeapMonth' => 0x25, // EAS 14.0
			'FirstDayOfWeek' => 0x26 // EAS 14.1
		],
		// 10 ResolveRecipients
		0x0A => [
			'ResolveRecipients' => 0x05,
			'Response' => 0x06,
			'Status' => 0x07,
			'Type' => 0x08,
			'Recipient' => 0x09,
			'DisplayName' => 0x0A,
			'EmailAddress' => 0x0B,
			'Certificates' => 0x0C,
			'Certificate' => 0x0D,
			'MiniCertificate' => 0x0E,
			'Options' => 0x0F,
			'To' => 0x10,
			'CertificateRetrieval' => 0x11,
			'RecipientCount' => 0x12,
			'MaxCertificates' => 0x13,
			'MaxAmbiguousRecipients' => 0x14,
			'CertificateCount' => 0x15,
			'Availability' => 0x16,
			'StartTime' => 0x17,
			'EndTime' => 0x18,
			'MergedFreeBusy' => 0x19,
			// 14.1
			'Picture' => 0x1A,
			'MaxSize' => 0x1B,
			'Data' => 0x1C,
			'MaxPictures' => 0x1D
		],
		// #11 ValidateCert
		0x0B => [
			'ValidateCert' => 0x05,
			'Certificates' => 0x06,
			'Certificate' => 0x07,
			'CertificateChain' => 0x08,
			'CheckCRL' => 0x09,
			'Status' => 0x0A,
		],
		// #12 Contacts2
		0x0C => [
			'CustomerId' => 0x05,
			'GovernmentId' => 0x06,
			'IMAddress' => 0x07,
			'IMAddress2' => 0x08,
			'IMAddress3' => 0x09,
			'ManagerName' => 0x0A,
			'CompanyMainPhone' => 0x0B,
			'AccountName' => 0x0C,
			'NickName' => 0x0D,
			'MMS' => 0x0E,
		],
		// #13 Ping
		0x0D => [
			'Ping' => 0x05,
			'AutdState' => 0x06,
			'Status' => 0x07,
			'HeartbeatInterval' => 0x08,
			'Folders' => 0x09,
			'Folder' => 0x0A,
			'ServerEntryId' => 0x0B,
			'FolderType' => 0x0C,
			'MaxFolders' => 0x0D,
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
			'Search' => 0x05,
			'Store' => 0x07,
			'Name' => 0x08,
			'Query' => 0x09,
			'Options' => 0x0A,
			'Range' => 0x0B,
			'Status' => 0x0C,
			'Response' => 0x0D,
			'Result' => 0x0E,
			'Properties' => 0x0F,
			'Total' => 0x10,
			'EqualTo' => 0x11,
			'Value' => 0x12,
			'And' => 0x13,
			'Or' => 0x14,
			'FreeText' => 0x15,
			'DeepTraversal' => 0x17,
			'LongId' => 0x18,
			'RebuildResults' => 0x19,
			'LessThan' => 0x1A,
			'GreaterThan' => 0x1B,
			'Schema' => 0x1C,
			'Supported' => 0x1D,
			// EAS 12.1
			'UserName' => 0x1E,
			'Password' => 0x1F,
			'ConversationId' => 0x20,
			// EAS 14.1
			'Picture' => 0x21,
			'MaxSize' => 0x22,
			'MaxPictures' => 0x23,
		],
		// #16 GAL (Global Address List)
		0x10 => [
			'DisplayName' => 0x05,
			'Phone' => 0x06,
			'Office' => 0x07,
			'Title' => 0x08,
			'Company' => 0x09,
			'Alias' => 0x0A,
			'FirstName' => 0x0B,
			'LastName' => 0x0C,
			'HomePhone' => 0x0D,
			'MobilePhone' => 0x0E,
			'EmailAddress' => 0x0F,
			// 14.1
			'Picture' => 0x10,
			'Status' => 0x11,
			'Data' => 0x12,
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
			'LinkId' => 0x05,
			'DisplayName' => 0x06,
			'IsFolder' => 0x07,
			'CreationDate' => 0x08,
			'LastModifiedDate' => 0x09,
			'IsHidden' => 0x0A,
			'ContentLength' => 0x0B,
			'ContentType' => 0x0C,
		],
		// #20 ItemOperations
		0x14 => [
			'ItemOperations' => 0x05,
			'Fetch' => 0x06,
			'Store' => 0x07,
			'Options' => 0x08,
			'Range' => 0x09,
			'Total' => 0x0A,
			'Properties' => 0x0B,
			'Data' => 0x0C,
			'Status' => 0x0D,
			'Response' => 0x0E,
			'Version' => 0x0F,
			'Schema' => 0x10,
			'Part' => 0x11,
			'EmptyFolderContent' => 0x12,
			'DeleteSubFolders' => 0x13,
			// EAS 12.1
			'UserName' => 0x14,
			'Password' => 0x15,
			// EAS 14.0
			'Move' => 0x16,
			'DstFldId' => 0x17,
			'ConversationId' => 0x18,
			'MoveAlways' => 0x19,
		],
		// #21 ComposeMail (14.0)
		0x15 => [
			'SendMail' => 0x05,
			'SmartForward' => 0x06,
			'SmartReply' => 0x07,
			'SaveInSentItems' => 0x08,
			'ReplaceMime' => 0x09,
			'Type' => 0x0A,
			'Source' => 0x0B,
			'FolderId' => 0x0C,
			'ItemId' => 0x0D,
			'LongId' => 0x0E,
			'InstanceId' => 0x0F,
			'MIME' => 0x10,
			'ClientId' => 0x11,
			'Status' => 0x12,
			// 14.1
			'AccountId' => 0x13,
			// EAS 16.0
			'Forwardees' => 0x15,
			'Forwardee' => 0x16,
			'ForwardeeName' => 0x17,
			'ForwardeeEmail' => 0x18
		],
		// #22 Email2 (14.0)
		0x16 => [
			'UmCallerId' => 0x05,
			'UmUserNotes' => 0x06,
			'UmAttDuration' => 0x07,
			'UmAttOrder' => 0x08,
			'ConversationId' => 0x09,
			'ConversationIndex' => 0x0A,
			'LastVerbExecuted' => 0x0B,
			'LastVerbExecutionTime' => 0x0C,
			'ReceivedAsBcc' => 0x0D,
			'Sender' => 0x0E,
			'CalendarType' => 0x0F,
			'IsLeapMonth' => 0x10,
			// 14.1
			'AccountId' => 0x11,
			'FirstDayOfWeek' => 0x12,
			'MeetingMessageType' => 0x13,
			// EAS 16.0
			'IsDraft' => 0x15,
			'Bcc' => 0x16,
			'Send' => 0x17
		],
		// #23 Notes (14.0)
		0x17 => [
			'Subject' => 0x05,
			'MessageClass' => 0x06,
			'LastModifiedDate' => 0x07,
			'Categories' => 0x08,
			'Category' => 0x09
		],
		// #24 RightsManagement (14.1)
		0x18 => [
			'RightsManagementSupport' => 0x05,
			'RightsManagementTemplates' => 0x06,
			'RightsManagementTemplate' => 0x07,
			'RightsManagementLicense' => 0x08,
			'EditAllowed' => 0x09,
			'ReplyAllowed' => 0x0A,
			'ReplyAllAllowed' => 0x0B,
			'ForwardAllowed' => 0x0C,
			'ModifyRecipientsAllowed' => 0x0D,
			'ExtractAllowed' => 0x0E,
			'PrintAllowed' => 0x0F,
			'ExportAllowed' => 0x10,
			'ProgrammaticAccessAllowed' => 0x11,
			'Owner' => 0x12,
			'ContentExpiryDate' => 0x13,
			'TemplateID' => 0x14,
			'TemplateName' => 0x15,
			'TemplateDescription' => 0x16,
			'ContentOwner' => 0x17,
			'RemoveRightsManagementDistribution' => 0x18
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
			elseif ($property instanceof EasCollection) {
				$cpage = $page;
				foreach ($property as $entry) {
					// evaluate if code page changed on the last iteration
					if ($cpage != $page) {
						$page = $cpage;
						$this->_writeByte($stream, EasXml::CODESPACE);
						$this->_writeByte($stream, $page);
					}
					// write node start
					$this->_writeNodeStart($stream, $page, $token, true, false);
					// write node contents
					$this->_writeBodyFromObject($stream, $entry, $page);
					// write node end
					$this->_writeNodeEnd($stream);
				}

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