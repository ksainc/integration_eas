<?php
namespace OCA\EAS\Utile\Wbxml\CodePages;

use OCA\EAS\Utile\Wbxml\WBXMLCodePage;

class ExchangeActiveSync{

	public static function getCodePages(): array{
		return [
			0	=> new WBXMLCodePage(0,[
				0x05	=> 'Sync',
				0x06	=> 'Responses',
				0x07	=> 'Add',
				0x08	=> 'Change',
				0x09	=> 'Delete',
				0x0A	=> 'Fetch',
				0x0B	=> 'SyncKey',
				0x0C	=> 'ClientId',
				0x0D	=> 'ServerId',
				0x0E	=> 'Status',
				0x0F	=> 'Collection',
				0x10	=> 'Class',
				0x12	=> 'CollectionId',
				0x13	=> 'GetChanges',
				0x14	=> 'MoreAvailable',
				0x15	=> 'WindowSize',
				0x16	=> 'Commands',
				0x17	=> 'Options',
				0x18	=> 'FilterType',

				0x1B	=> 'Conflict',
				0x1C	=> 'Collections',
				0x1D	=> 'ApplicationData',
				0x1E	=> 'DeletesAsMoves',
				0x20	=> 'Supported',
				0x21	=> 'SoftDelete',
				0x22	=> 'MIMESupport',
				0x23	=> 'MIMETruncation',
				0x24	=> 'Wait',                // 12.1, 14.0, 14.1, 16.0, 16.1
				0x25	=> 'Limit',               // 12.1, 14.0, 14.1, 16.0, 16.1
				0x26	=> 'Partial',             // 12.1, 14.0, 14.1, 16.0, 16.1
				0x27	=> 'ConversationMode',    // 14.0, 14.1, 16.0, 16.1
				0x28	=> 'MaxItems',            // 14.0, 14.1, 16.0, 16.1
				0x29	=> 'HeartbeatInterval',   // 14.0, 14.1, 16.0, 16.1
			],'AirSync:','airsync'),
			1	=> new WBXMLCodePage(1,[
				0x05	=> 'Anniversary',
				0x06	=> 'AssistantName',
				0x07	=> 'AssistantTelephoneNumber',
				0x08	=> 'Birthday',
				0x0C	=> 'Business2PhoneNumber',
				0x0D	=> 'BusinessCity',
				0x0E	=> 'BusinessCountry',
				0x0F	=> 'BusinessPostalCode',
				0x10	=> 'BusinessState',
				0x11	=> 'BusinessStreet',
				0x12	=> 'BusinessFaxNumber',
				0x13	=> 'BusinessPhoneNumber',
				0x14	=> 'CarPhoneNumber',
				0x15	=> 'Categories',
				0x16	=> 'Category',
				0x17	=> 'Children',
				0x18	=> 'Child',
				0x19	=> 'CompanyName',
				0x1A	=> 'Department',
				0x1B	=> 'Email1Address',
				0x1C	=> 'Email2Address',
				0x1D	=> 'Email3Address',
				0x1E	=> 'FileAs',
				0x1F	=> 'FirstName',
				0x20	=> 'Home2PhoneNumber',
				0x21	=> 'HomeCity',
				0x22	=> 'HomeCountry',
				0x23	=> 'HomePostalCode',
				0x24	=> 'HomeState',
				0x25	=> 'HomeStreet',
				0x26	=> 'HomeFaxNumber',
				0x27	=> 'HomePhoneNumber',
				0x28	=> 'JobTitle',
				0x29	=> 'LastName',
				0x2A	=> 'MiddleName',
				0x2B	=> 'MobilePhoneNumber',
				0x2C	=> 'OfficeLocation',
				0x2D	=> 'OtherCity',
				0x2E	=> 'OtherCountry',
				0x2F	=> 'OtherPostalCode',
				0x30	=> 'OtherState',
				0x31	=> 'OtherStreet',
				0x32	=> 'PagerNumber',
				0x33	=> 'RadioPhoneNumber',
				0x34	=> 'Spouse',
				0x35	=> 'Suffix',
				0x36	=> 'Title',
				0x37	=> 'Webpage',
				0x38	=> 'YomiCompanyName',
				0x39	=> 'YomiFirstName',
				0x3A	=> 'YomiLastName',
				0x3C	=> 'Picture',
				0x3D	=> 'Alias',           // 14.0, 14.1, 16.0, 16.1
				0x3E	=> 'WeightedRank',    // 14.0, 14.1, 16.0, 16.1
			],'Contacts:','contacts'),
			2	=> new WBXMLCodePage(2,[
				0x0F	=> 'DateReceived',
				0x11	=> 'DisplayTo',
				0x12	=> 'Importance',
				0x13	=> 'MessageClass',
				0x14	=> 'Subject',
				0x15	=> 'Read',
				0x16	=> 'To',
				0x17	=> 'CC',
				0x18	=> 'From',
				0x19	=> 'ReplyTo',
				0x1A	=> 'AllDayEvent',
				0x1B	=> 'Categories',                  // 14.0, 14.1, 16.0, 16.1
				0x1C	=> 'Category',                    // 14.0, 14.1, 16.0, 16.1
				0x1D	=> 'DTStamp',
				0x1E	=> 'EndTime',
				0x1F	=> 'InstanceType',
				0x20	=> 'BusyStatus',
				0x21	=> 'Location',                    // 2.5, 12.0, 12.1, 14.0, 14.1 - See Note 3.
				0x22	=> 'MeetingRequest',
				0x23	=> 'Organizer',
				0x24	=> 'RecurrenceId',
				0x25	=> 'Reminder',
				0x26	=> 'ResponseRequested',
				0x27	=> 'Recurrences',
				0x28	=> 'Recurrence',
				0x29	=> 'Recurrence_Type',
				0x2A	=> 'Recurrence_Until',
				0x2B	=> 'Recurrence_Occurrences',
				0x2C	=> 'Recurrence_Interval',
				0x2D	=> 'Recurrence_DayOfWeek',
				0x2E	=> 'Recurrence_DayOfMonth',
				0x2F	=> 'Recurrence_WeekOfMonth',
				0x30	=> 'Recurrence_MonthOfYear',
				0x31	=> 'StartTime',
				0x32	=> 'Sensitivity',
				0x33	=> 'TimeZone',
				0x34	=> 'GlobalObjId',                 // 2.5, 12.0, 12.1, 14.0, 14.1 - See Note 4.
				0x35	=> 'ThreadTopic',
				0x39	=> 'InternetCPID',
				0x3A	=> 'Flag',                        // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x3B	=> 'FlagStatus',                  // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x3C	=> 'ContentClass',                // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x3D	=> 'FlagType',                    // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x3E	=> 'CompleteTime',                // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x3F	=> 'DisallowNewTimeProposal',     // 14.0, 14.1, 16.0, 16.1
			],'Email:','email'),
			//No codepage with number 3
			4	=> new WBXMLCodePage(4,[
				0x05	=> 'TimeZone',
				0x06	=> 'AllDayEvent',
				0x07	=> 'Attendees',
				0x08	=> 'Attendee',
				0x09	=> 'Attendee_Email',
				0x0A	=> 'Attendee_Name',
				// 				0x0A	=> 'Body',             // 2.5 - See Note 2
				// 				0x0B	=> 'BodyTruncated',

				0x0D	=> 'BusyStatus',
				0x0E	=> 'Categories',
				0x0F	=> 'Category',
				0x11	=> 'DTStamp',
				0x12	=> 'EndTime',
				0x13	=> 'Exception',
				0x14	=> 'Exceptions',
				0x15	=> 'Exception_Deleted',
				0x16	=> 'Exception_StartTime',     // 2.5, 12.0, 12.1, 14.0, 14.1
				0x17	=> 'Location',                // 2.5, 12.0, 12.1, 14.0, 14.1 - See Note 2
				0x18	=> 'MeetingStatus',
				0x19	=> 'Organizer_Email',
				0x1A	=> 'Organizer_Name',
				0x1B	=> 'Recurrence',
				0x1C	=> 'Recurrence_Type',
				0x1D	=> 'Recurrence_Until',
				0x1E	=> 'Recurrence_Occurrences',
				0x1F	=> 'Recurrence_Interval',
				0x20	=> 'Recurrence_DayOfWeek',
				0x21	=> 'Recurrence_DayOfMonth',
				0x22	=> 'Recurrence_WeekOfMonth',
				0x23	=> 'Recurrence_MonthOfYear',
				0x24	=> 'Reminder',
				0x25	=> 'Sensitivity',
				0x26	=> 'Subject',
				0x27	=> 'StartTime',
				0x28	=> 'UID',
				0x29	=> 'Attendee_Status',             // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x2A	=> 'Attendee_Type',               // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x33	=> 'DisallowNewTimeProposal',     // 14.0, 14.1, 16.0, 16.1
				0x34	=> 'ResponseRequested',           // 14.0, 14.1, 16.0, 16.1
				0x35	=> 'AppointmentReplyTime',        // 14.0, 14.1, 16.0, 16.1
				0x36	=> 'ResponseType',                // 14.0, 14.1, 16.0, 16.1
				0x37	=> 'CalendarType',                // 14.0, 14.1, 16.0, 16.1
				0x38	=> 'IsLeapMonth',                 // 14.0, 14.1, 16.0, 16.1
				0x39	=> 'FirstDayOfWeek',              // 14.1, 16.0, 16.1
				0x3A	=> 'OnlineMeetingConfLink',       // 14.1, 16.0, 16.1
				0x3B	=> 'OnlineMeetingExternalLink',   // 14.1, 16.0, 16.1
				0x3C	=> 'ClientUid',                   // 16.0, 16.1
			],'Calendar:','calendar'),
			5	=> new WBXMLCodePage(5,[
				0x05	=> 'MoveItems',       // All
				0x06	=> 'Move',            // All
				0x07	=> 'SrcMsgId',        // All
				0x08	=> 'SrcFldId',        // All
				0x09	=> 'DstFldId',        // All
				0x0A	=> 'Response',        // All
				0x0B	=> 'Status',          // All
				0x0C	=> 'DstMsgId',        // All
			],'Move:','move'),
			6	=> new WBXMLCodePage(6,[
				0x05	=> 'GetItemEstimate',
				0x06	=> 'Version',
				0x07	=> 'Collections',
				0x08	=> 'Collection',
				0x09	=> 'Class',       // 2.5, 12.0, 12.1 - See Note 1
				0x0A	=> 'CollectionId',
				0x0B	=> 'DateTime',
				0x0C	=> 'Estimate',
				0x0D	=> 'Response',
				0x0E	=> 'Status',
			],'GetItemEstimate:','getitemestimate'),
			7	=> new WBXMLCodePage(7,[
				//				0x05	=> 'Folders',     // 2.5, 12.0, 12.1
				//				0x06	=> 'Folder',      // 2.5, 12.0, 12.1

				0x07	=> 'DisplayName',
				0x08	=> 'ServerId',
				0x09	=> 'ParentId',
				0x0A	=> 'Type',
				0x0C	=> 'Status',
				0x0E	=> 'Changes',
				0x0F	=> 'Add',
				0x10	=> 'Delete',
				0x11	=> 'Update',
				0x12	=> 'SyncKey',
				0x13	=> 'FolderCreate',
				0x14	=> 'FolderDelete',
				0x15	=> 'FolderUpdate',
				0x16	=> 'FolderSync',
				0x17	=> 'Count',
			],'FolderHierarchy:','folderhierarchy'),
			8	=> new WBXMLCodePage(8,[
				0x05	=> 'CalendarId',
				0x06	=> 'CollectionId',
				0x07	=> 'MeetingResponse',
				0x08	=> 'RequestId',
				0x09	=> 'Request',
				0x0A	=> 'Result',
				0x0B	=> 'Status',
				0x0C	=> 'UserResponse',
				0x0E	=> 'InstanceId',              // 14.1, 16.0, 16.1
				0x10	=> 'ProposedStartTime',       // 16.1
				0x11	=> 'ProposedEndTime',         // 16.1
				0x12	=> 'SendResponse',            // 16.0, 16.1
			],'MeetingResponse:','meetingresponse'),
			9	=> new WBXMLCodePage(9,[
				0x08	=> 'Categories',
				0x09	=> 'Category',
				0x0A	=> 'Complete',
				0x0B	=> 'DateCompleted',
				0x0C	=> 'DueDate',
				0x0D	=> 'UTCDueDate',
				0x0E	=> 'Importance',
				0x0F	=> 'Recurrence',
				0x10	=> 'Recurrence_Type',
				0x11	=> 'Recurrence_Start',
				0x12	=> 'Recurrence_Until',
				0x13	=> 'Recurrence_Occurrences',
				0x14	=> 'Recurrence_Interval',
				0x15	=> 'Recurrence_DayOfMonth',
				0x16	=> 'Recurrence_DayOfWeek',
				0x17	=> 'Recurrence_WeekOfMonth',
				0x18	=> 'Recurrence_MonthOfYear',
				0x19	=> 'Recurrence_Regenerate',
				0x1A	=> 'Recurrence_DeadOccur',
				0x1B	=> 'ReminderSet',
				0x1C	=> 'ReminderTime',
				0x1D	=> 'Sensitivity',
				0x1E	=> 'StartDate',
				0x1F	=> 'UTCStartDate',
				0x20	=> 'Subject',
				0x22	=> 'OrdinalDate',     // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x23	=> 'SubOrdinalDate',  // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x24	=> 'CalendarType',    // 14.0, 14.1, 16.0, 16.1
				0x25	=> 'IsLeapMonth',     // 14.0, 14.1, 16.0, 16.1
				0x26	=> 'FirstDayOfWeek',  // 14.1, 16.0, 16.1
			],'Tasks:','tasks'),
			10	=> new WBXMLCodePage(10,[
				0x05	=> 'ResolveRecipients',
				0x06	=> 'Response',
				0x07	=> 'Status',
				0x08	=> 'Type',
				0x09	=> 'Recipient',
				0x0A	=> 'DisplayName',
				0x0B	=> 'EmailAddress',
				0x0C	=> 'Certificates',
				0x0D	=> 'Certificate',
				0x0E	=> 'MiniCertificate',
				0x0F	=> 'Options',
				0x10	=> 'To',
				0x11	=> 'CertificateRetrieval',
				0x12	=> 'RecipientCount',
				0x13	=> 'MaxCertificates',
				0x14	=> 'MaxAmbiguousRecipients',
				0x15	=> 'CertificateCount',
				0x16	=> 'Availability',       // 14.0, 14.1, 16.0, 16.1
				0x17	=> 'StartTime',          // 14.0, 14.1, 16.0, 16.1
				0x18	=> 'EndTime',            // 14.0, 14.1, 16.0, 16.1
				0x19	=> 'MergedFreeBusy',     // 14.0, 14.1, 16.0, 16.1
				0x1A	=> 'Picture',            // 14.1, 16.0, 16.1
				0x1B	=> 'MaxSize',            // 14.1, 16.0, 16.1
				0x1C	=> 'Data',               // 14.1, 16.0, 16.1
				0x1D	=> 'MaxPictures',        // 14.1, 16.0, 16.1
			],'ResolveRecipients:','resolverecipients'),
			11	=> new WBXMLCodePage(11,[
				0x05	=> 'ValidateCert',
				0x06	=> 'Certificates',
				0x07	=> 'Certificate',
				0x08	=> 'CertificateChain',
				0x09	=> 'CheckCRL',
				0x0A	=> 'Status',
			],'ValidateCert:','validatecert'),
			12	=> new WBXMLCodePage(12,[
				0x05	=> 'CustomerId',
				0x06	=> 'GovernmentId',
				0x07	=> 'IMAddress',
				0x08	=> 'IMAddress2',
				0x09	=> 'IMAddress3',
				0x0A	=> 'ManagerName',
				0x0B	=> 'CompanyMainPhone',
				0x0C	=> 'AccountName',
				0x0D	=> 'NickName',
				0x0E	=> 'MMS',
			],'Contacts2:','contacts2'),
			13	=> new WBXMLCodePage(13,[
				0x05	=> 'Ping',
				0x06	=> 'AutdState',  // Per MS-ASWBXML, this tag is not used by protocol
				0x07	=> 'Status',
				0x08	=> 'HeartbeatInterval',
				0x09	=> 'Folders',
				0x0A	=> 'Folder',
				0x0B	=> 'Id',
				0x0C	=> 'Class',
				0x0D	=> 'MaxFolders',
			],'Ping:','ping'),
			14	=> new WBXMLCodePage(14,[
				0x05	=> 'Provision',
				0x06	=> 'Policies',
				0x07	=> 'Policy',
				0x08	=> 'PolicyType',
				0x09	=> 'PolicyKey',
				0x0A	=> 'Data',
				0x0B	=> 'Status',
				0x0C	=> 'RemoteWipe',
				0x0D	=> 'EASProvisionDoc',                        // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0E	=> 'DevicePasswordEnabled',                  // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0F	=> 'AlphanumericDevicePasswordRequired',     // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x10	=> 'RequireStorageCardEncryption',           // 12.1, 14.0, 14.1, 16.0, 16.1
				0x11	=> 'PasswordRecoveryEnabled',                // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x13	=> 'AttachmentsEnabled',                     // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x14	=> 'MinDevicePasswordLength',                // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x15	=> 'MaxInactivityTimeDeviceLock',            // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x16	=> 'MaxDevicePasswordFailedAttempts',        // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x17	=> 'MaxAttachmentSize',                      // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x18	=> 'AllowSimpleDevicePassword',              // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x19	=> 'DevicePasswordExpiration',               // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x1A	=> 'DevicePasswordHistory',                  // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x1B	=> 'AllowStorageCard',                       // 12.1, 14.0, 14.1, 16.0, 16.1
				0x1C	=> 'AllowCamera',                            // 12.1, 14.0, 14.1, 16.0, 16.1
				0x1D	=> 'RequireDeviceEncryption',                // 12.1, 14.0, 14.1, 16.0, 16.1
				0x1E	=> 'AllowUnsignedApplications',              // 12.1, 14.0, 14.1, 16.0, 16.1
				0x1F	=> 'AllowUnsignedInstallationPackages',      // 12.1, 14.0, 14.1, 16.0, 16.1
				0x20	=> 'MinDevicePasswordComplexCharacters',     // 12.1, 14.0, 14.1, 16.0, 16.1
				0x21	=> 'AllowWiFi',                              // 12.1, 14.0, 14.1, 16.0, 16.1
				0x22	=> 'AllowTextMessaging',                     // 12.1, 14.0, 14.1, 16.0, 16.1
				0x23	=> 'AllowPOPIMAPEmail',                      // 12.1, 14.0, 14.1, 16.0, 16.1
				0x24	=> 'AllowBluetooth',                         // 12.1, 14.0, 14.1, 16.0, 16.1
				0x25	=> 'AllowIrDA',                              // 12.1, 14.0, 14.1, 16.0, 16.1
				0x26	=> 'RequireManualSyncWhenRoaming',           // 12.1, 14.0, 14.1, 16.0, 16.1
				0x27	=> 'AllowDesktopSync',                       // 12.1, 14.0, 14.1, 16.0, 16.1
				0x28	=> 'MaxCalendarAgeFilter',                   // 12.1, 14.0, 14.1, 16.0, 16.1
				0x29	=> 'AllowHTMLEmail',                         // 12.1, 14.0, 14.1, 16.0, 16.1
				0x2A	=> 'MaxEmailAgeFilter',                      // 12.1, 14.0, 14.1, 16.0, 16.1
				0x2B	=> 'MaxEmailBodyTruncationSize',             // 12.1, 14.0, 14.1, 16.0, 16.1
				0x2C	=> 'MaxEmailHTMLBodyTruncationSize',         // 12.1, 14.0, 14.1, 16.0, 16.1
				0x2D	=> 'RequireSignedSMIMEMessages',             // 12.1, 14.0, 14.1, 16.0, 16.1
				0x2E	=> 'RequireEncryptedSMIMEMessages',          // 12.1, 14.0, 14.1, 16.0, 16.1
				0x2F	=> 'RequireSignedSMIMEAlgorithm',            // 12.1, 14.0, 14.1, 16.0, 16.1
				0x30	=> 'RequireEncryptionSMIMEAlgorithm',            // 12.1, 14.0, 14.1, 16.0, 16.1
				0x31	=> 'AllowSMIMEEncryptionAlgorithmNegotiation',   // 12.1, 14.0, 14.1, 16.0, 16.1
				0x32	=> 'AllowSMIMESoftCerts',                    // 12.1, 14.0, 14.1, 16.0, 16.1
				0x33	=> 'AllowBrowser',                           // 12.1, 14.0, 14.1, 16.0, 16.1
				0x34	=> 'AllowConsumerEmail',                     // 12.1, 14.0, 14.1, 16.0, 16.1
				0x35	=> 'AllowRemoteDesktop',                     // 12.1, 14.0, 14.1, 16.0, 16.1
				0x36	=> 'AllowInternetSharing',                   // 12.1, 14.0, 14.1, 16.0, 16.1
				0x37	=> 'UnapprovedInROMApplicationList',         // 12.1, 14.0, 14.1, 16.0, 16.1
				0x38	=> 'ApplicationName',                        // 12.1, 14.0, 14.1, 16.0, 16.1
				0x39	=> 'ApprovedApplicationList',                // 12.1, 14.0, 14.1, 16.0, 16.1
				0x3A	=> 'Hash',                                   // 12.1, 14.0, 14.1, 16.0, 16.1
				0x3B	=> 'AccountOnlyRemoteWipe',                  // 16.1
			],'Provision:','provision'),
			15	=> new WBXMLCodePage(15,[
				0x05	=> 'Search',
				0x07	=> 'Store',
				0x08	=> 'Name',
				0x09	=> 'Query',
				0x0A	=> 'Options',
				0x0B	=> 'Range',
				0x0C	=> 'Status',
				0x0D	=> 'Response',
				0x0E	=> 'Result',
				0x0F	=> 'Properties',
				0x10	=> 'Total',
				0x11	=> 'EqualTo',        // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x12	=> 'Value',          // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x13	=> 'And',            // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x14	=> 'Or',             // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x15	=> 'FreeText',       // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x17	=> 'DeepTraversal',  // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x18	=> 'LongId',         // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x19	=> 'RebuildResults', // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x1A	=> 'LessThan',       // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x1B	=> 'GreaterThan',    // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x1E	=> 'UserName',       // 12.1, 14.0, 14.1, 16.0, 16.1
				0x1F	=> 'Password',       // 12.1, 14.0, 14.1, 16.0, 16.1
				0x20	=> 'ConversationId', // 12.1, 14.0, 14.1, 16.0, 16.1
				0x21	=> 'Picture',        // 14.1, 16.0, 16.1
				0x22	=> 'MaxSize',        // 14.1, 16.0, 16.1
				0x23	=> 'MaxPictures',    // 14.1, 16.0, 16.1
			],'Search:','search'),
			16	=> new WBXMLCodePage(16,[
				0x05	=> 'DisplayName',
				0x06	=> 'Phone',
				0x07	=> 'Office',
				0x08	=> 'Title',
				0x09	=> 'Company',
				0x0A	=> 'Alias',
				0x0B	=> 'FirstName',
				0x0C	=> 'LastName',
				0x0D	=> 'HomePhone',
				0x0E	=> 'MobilePhone',
				0x0F	=> 'EmailAddress',
				0x10	=> 'Picture',        // 14.1, 16.0, 16.1
				0x11	=> 'Status',         // 14.1, 16.0, 16.1
				0x12	=> 'Data',           // 14.1, 16.0, 16.1
			],'GAL:','gal'),
			17	=> new WBXMLCodePage(17,[
				0x05	=> 'BodyPreference',     // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x06	=> 'Type',               // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x07	=> 'TruncationSize',     // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x08	=> 'AllOrNone',          // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0A	=> 'Body',               // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0B	=> 'Data',               // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0C	=> 'EstimatedDataSize',  // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0D	=> 'Truncated',          // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0E	=> 'Attachments',        // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0F	=> 'Attachment',         // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x10	=> 'DisplayName',        // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x11	=> 'FileReference',      // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x12	=> 'Method',             // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x13	=> 'ContentId',          // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x14	=> 'ContentLocation',    // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x15	=> 'IsInline',           // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x16	=> 'NativeBodyType',     // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x17	=> 'ContentType',        // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x18	=> 'Preview',            // 14.0, 14.1, 16.0, 16.1
				0x19	=> 'BodyPartPreference', // 14.1, 16.0, 16.1
				0x1A	=> 'BodyPart',           // 14.1, 16.0, 16.1
				0x1B	=> 'Status',             // 14.1, 16.0, 16.1

				0x1C	=> 'Add',                //  16.0, 16.1
				0x1D	=> 'Delete',             //  16.0, 16.1
				0x1E	=> 'ClientId',           //  16.0, 16.1
				0x1F	=> 'Content',            //  16.0, 16.1
				0x20	=> 'Location',           //  16.0, 16.1
				0x21	=> 'Annotation',         //  16.0, 16.1
				0x22	=> 'Street',             //  16.0, 16.1
				0x23	=> 'City',               //  16.0, 16.1
				0x24	=> 'State',              //  16.0, 16.1
				0x25	=> 'Country',            //  16.0, 16.1
				0x26	=> 'PostalCode',         //  16.0, 16.1
				0x27	=> 'Latitude',           //  16.0, 16.1
				0x28	=> 'Longitude',          //  16.0, 16.1
				0x29	=> 'Accuracy',           //  16.0, 16.1
				0x2A	=> 'Altitude',           //  16.0, 16.1
				0x2B	=> 'AltitudeAccuracy',   //  16.0, 16.1
				0x2C	=> 'LocationUri',        //  16.0, 16.1
				0x2D	=> 'InstanceId',         //  16.0, 16.1
			],'AirSyncBase:','airsyncbase'),
			18	=> new WBXMLCodePage(18,[
				0x05	=> 'Settings',                       // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x06	=> 'Status',                         // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x07	=> 'Get',                            // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x08	=> 'Set',                            // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x09	=> 'Oof',                            // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0A	=> 'OofState',                       // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0B	=> 'StartTime',                      // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0C	=> 'EndTime',                        // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0D	=> 'OofMessage',                     // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0E	=> 'AppliesToInternal',              // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0F	=> 'AppliesToExternalKnown',         // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x10	=> 'AppliesToExternalUnknown',       // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x11	=> 'Enabled',                        // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x12	=> 'ReplyMessage',                   // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x13	=> 'BodyType',                       // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x14	=> 'DevicePassword',                 // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x15	=> 'Password',                       // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x16	=> 'DeviceInformation',              // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x17	=> 'Model',                          // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x18	=> 'IMEI',                           // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x19	=> 'FriendlyName',                   // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x1A	=> 'OS',                             // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x1B	=> 'OSLanguage',                     // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x1C	=> 'PhoneNumber',                    // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x1D	=> 'UserInformation',                // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x1E	=> 'EmailAddresses',                 // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x1F	=> 'SmtpAddress',                    // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x20	=> 'UserAgent',                      // 12.1, 14.0, 14.1, 16.0, 16.1
				0x21	=> 'EnableOutboundSMS',              // 14.0, 14.1, 16.0, 16.1
				0x22	=> 'MobileOperator',                 // 14.0, 14.1, 16.0, 16.1
				0x23	=> 'PrimarySmtpAddress',             // 14.1, 16.0, 16.1
				0x24	=> 'Accounts',                       // 14.1, 16.0, 16.1
				0x25	=> 'Account',                        // 14.1, 16.0, 16.1
				0x26	=> 'AccountId',                      // 14.1, 16.0, 16.1
				0x27	=> 'AccountName',                    // 14.1, 16.0, 16.1
				0x28	=> 'UserDisplayName',                // 14.1, 16.0, 16.1
				0x29	=> 'SendDisabled',                   // 14.1, 16.0, 16.1
				0x2B	=> 'RightsManagementInformation',    // 14.1, 16.0, 16.1
			],'Settings:','settings'),
			19	=> new WBXMLCodePage(19,[
				0x05	=> 'LinkId',             // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x06	=> 'DisplayName',        // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x07	=> 'IsFolder',           // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x08	=> 'CreationDate',       // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x09	=> 'LastModifiedDate',   // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0A	=> 'IsHidden',           // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0B	=> 'ContentLength',      // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0C	=> 'ContentType',        // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
			],'DocumentLibrary:','documentlibrary'),
			20	=> new WBXMLCodePage(20,[
				0x05	=> 'ItemOperations',         // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x06	=> 'Fetch',                  // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x07	=> 'Store',                  // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x08	=> 'Options',                // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x09	=> 'Range',                  // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0A	=> 'Total',                  // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0B	=> 'Properties',             // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0C	=> 'Data',                   // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0D	=> 'Status',                 // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0E	=> 'Response',               // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x0F	=> 'Version',                // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x10	=> 'Schema',                 // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x11	=> 'Part',                   // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x12	=> 'EmptyFolderContents',    // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x13	=> 'DeleteSubFolders',       // 12.0, 12.1, 14.0, 14.1, 16.0, 16.1
				0x14	=> 'UserName',               // 12.1, 14.0, 14.1, 16.0, 16.1
				0x15	=> 'Password',               // 12.1, 14.0, 14.1, 16.0, 16.1
				0x16	=> 'Move',                   // 14.0, 14.1, 16.0, 16.1, 16.1
				0x17	=> 'DstFldId',               // 14.0, 14.1, 16.0, 16.1, 16.1
				0x18	=> 'ConversationId',         // 14.0, 14.1, 16.0, 16.1, 16.1
				0x19	=> 'MoveAlways',             // 14.0, 14.1, 16.0, 16.1, 16.1
			],'ItemOperations:','itemoperations'),
			21	=> new WBXMLCodePage(21,[
				0x05	=> 'SendMail',					// 14.0, 14.1, 16.0, 16.1
				0x06	=> 'SmartForward',				// 14.0, 14.1, 16.0, 16.1
				0x07	=> 'SmartReply',				// 14.0, 14.1, 16.0, 16.1
				0x08	=> 'SaveInSentItems',			// 14.0, 14.1, 16.0, 16.1
				0x09	=> 'ReplaceMime',				// 14.0, 14.1, 16.0, 16.1
				0x0B	=> 'Source',					// 14.0, 14.1, 16.0, 16.1
				0x0C	=> 'FolderId',					// 14.0, 14.1, 16.0, 16.1
				0x0D	=> 'ItemId',					// 14.0, 14.1, 16.0, 16.1
				0x0E	=> 'LongId',					// 14.0, 14.1, 16.0, 16.1
				0x0F	=> 'InstanceId',				// 14.0, 14.1, 16.0, 16.1
				0x10	=> 'Mime',						// 14.0, 14.1, 16.0, 16.1
				0x11	=> 'ClientId',					// 14.0, 14.1, 16.0, 16.1
				0x12	=> 'Status',					// 14.0, 14.1, 16.0, 16.1
				0x13	=> 'AccountId',					// 14.1, 16.0, 16.1
				0x15	=> 'Forwardees',				// 16.0, 16.1
				0x16	=> 'Forwardee',					// 16.0, 16.1
				0x17	=> 'ForwardeeName',				// 16.0, 16.1
				0x18	=> 'ForwardeeEmail',			// 16.0, 16.1
			],'ComposeMail:','composemail'),
			22	=> new WBXMLCodePage(22,[
				0x05	=> 'UmCallerID',             // 14.0, 14.1, 16.0, 16.1
				0x06	=> 'UmUserNotes',            // 14.0, 14.1, 16.0, 16.1
				0x07	=> 'UmAttDuration',          // 14.0, 14.1, 16.0, 16.1
				0x08	=> 'UmAttOrder',             // 14.0, 14.1, 16.0, 16.1
				0x09	=> 'ConversationId',         // 14.0, 14.1, 16.0, 16.1
				0x0A	=> 'ConversationIndex',      // 14.0, 14.1, 16.0, 16.1
				0x0B	=> 'LastVerbExecuted',       // 14.0, 14.1, 16.0, 16.1
				0x0C	=> 'LastVerbExecutionTime',  // 14.0, 14.1, 16.0, 16.1
				0x0D	=> 'ReceivedAsBcc',          // 14.0, 14.1, 16.0, 16.1
				0x0E	=> 'Sender',                 // 14.0, 14.1, 16.0, 16.1
				0x0F	=> 'CalendarType',           // 14.0, 14.1, 16.0, 16.1
				0x10	=> 'IsLeapMonth',            // 14.0, 14.1, 16.0, 16.1
				0x11	=> 'AccountId',              // 14.1, 16.0, 16.1
				0x12	=> 'FirstDayOfWeek',         // 14.1, 16.0, 16.1
				0x13	=> 'MeetingMessageType',     // 14.1, 16.0, 16.1

				0x15	=> 'IsDraft',                // 16.0, 16.1
				0x16	=> 'Bcc',                    // 16.0, 16.1
				0x17	=> 'Send',                   // 16.0, 16.1
			],'Email2:','email2'),
			23	=> new WBXMLCodePage(23,[
				0x05	=> 'Subject',            // 14.0, 14.1, 16.0, 16.1
				0x06	=> 'MessageClass',       // 14.0, 14.1, 16.0, 16.1
				0x07	=> 'LastModifiedDate',   // 14.0, 14.1, 16.0, 16.1
				0x08	=> 'Categories',         // 14.0, 14.1, 16.0, 16.1
				0x09	=> 'Category',           // 14.0, 14.1, 16.0, 16.1
			],'Notes:','notes'),
			24	=> new WBXMLCodePage(24,[
				0x05	=> 'RightsManagementSupport',    // 14.1, 16.0, 16.1
				0x06	=> 'RightsManagementTemplates',  // 14.1, 16.0, 16.1
				0x07	=> 'RightsManagementTemplate',   // 14.1, 16.0, 16.1
				0x08	=> 'RightsManagementLicense',    // 14.1, 16.0, 16.1
				0x09	=> 'EditAllowed',                // 14.1, 16.0, 16.1
				0x0A	=> 'ReplyAllowed',               // 14.1, 16.0, 16.1
				0x0B	=> 'ReplyAllAllowed',            // 14.1, 16.0, 16.1
				0x0C	=> 'ForwardAllowed',             // 14.1, 16.0, 16.1
				0x0D	=> 'ModifyRecipientsAllowed',    // 14.1, 16.0, 16.1
				0x0E	=> 'ExtractAllowed',             // 14.1, 16.0, 16.1
				0x0F	=> 'PrintAllowed',               // 14.1, 16.0, 16.1
				0x10	=> 'ExportAllowed',              // 14.1, 16.0, 16.1
				0x11	=> 'ProgrammaticAccessAllowed',  // 14.1, 16.0, 16.1
				0x12	=> 'RMOwner',                    // 14.1, 16.0, 16.1
				0x13	=> 'ContentExpiryDate',          // 14.1, 16.0, 16.1
				0x14	=> 'TemplateID',                 // 14.1, 16.0, 16.1
				0x15	=> 'TemplateName',               // 14.1, 16.0, 16.1
				0x16	=> 'TemplateDescription',        // 14.1, 16.0, 16.1
				0x17	=> 'ContentOwner',               // 14.1, 16.0, 16.1
				0x18	=> 'RemoveRightsManagementDistribution', // 14.1, 16.0, 16.1
			],'RightsManagement:','rightsmanagement'),
			25	=> new WBXMLCodePage(25,[
				0x05	=> 'Find',                       // 16.1
				0x06	=> 'SearchId',                   // 16.1
				0x07	=> 'ExecuteSearch',              // 16.1
				0x08	=> 'MailBoxSearchCriterion',     // 16.1
				0x09	=> 'Query',                      // 16.1
				0x0A	=> 'Status',                     // 16.1
				0x0B	=> 'FreeText',                   // 16.1
				0x0C	=> 'Options',                    // 16.1
				0x0D	=> 'Range',                      // 16.1
				0x0E	=> 'DeepTraversal',              // 16.1
				0x11	=> 'Response',                   // 16.1
				0x12	=> 'Result',                     // 16.1
				0x13	=> 'Properties',                 // 16.1
				0x14	=> 'Preview',                    // 16.1
				0x15	=> 'HasAttachments',             // 16.1
				0x16	=> 'Total',                      // 16.1
				0x17	=> 'DisplayCc',                  // 16.1
				0x18	=> 'DisplayBcc',                 // 16.1
			],'Find:','find'),
		];
	}

}