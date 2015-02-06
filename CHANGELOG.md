All other releases are described directly on [GitHub Releases page](https://github.com/keboola/syrup-component-bundle/releases)

## 1.12.1
 * [Chore]      Added HookExecutorInterface, method HookExecutorInterface::postExecution implemented in JobCommand (runs after job is executed)

## 1.12.0       
 * [Test]       Added CI tests
 * [BC Break]   JobWarningException renamed to JobException, this exception can store Job result, which is saved to ES, when Job terminates with this exception

## 1.11.0
 * [Chore]      Added elastic search response to JobManager::indexJob logging
 * [Refactor]   JobCommand initialization method replaced with simpler init method, which is called from execute method. This ensures better error handling during init.
 * [BC Break]   JobManager::getJobs now supports 'since' and 'until' arguments to limit query result

## 1.10.17
 * [Fix]        Job id forced to int in JobManager::getJobs method
 * [Fix]        "Missing StorageApi token" exception is now a UserException
 
## 1.10.16
 * [Refactor]   Logging to SAPI, small text change

## 1.10.15
 * [Refactor]   Logging to SAPI events: Removed description added job and request info
 
## 1.10.14
 * [Feat]       Log some information when creating new Job

## 1.10.13
 * [Fix]        Typo

## 1.10.12      
 * [Feat]       New job status "warning"

## 1.10.11
 * [Fix]        GenerateComponentCommand: parameters.yml indentation

## 1.10.10
 * [Fix]        Don't log debug level to sapi events
 
## 1.10.9
 * [Fix]        Component generator was using deprecated dialogHelper

## 1.10.8
 * [Refactor]   Retry job on initialization error

## 1.10.7
 * [Fix]        SQS queue receive VisibilityTimeout set to 3600s to prevent duplication of messages from SQS
 
## 1.10.6
 * [Fix]        Don't log channel doctrine to sapi events

## 1.10.5
 * [Refactor]   SyrupComponentException now accepts 4th argument to constructor - $data - array of addtionial information for logging        

## 1.10.4
 * [Fix]        Better event logging to SAPI
 * [Fix]        Better handling of job status and job results during fatal errors and on repeated runs of the same job

## 1.10.3
 * [Feature]    SQS queue is now configurable from parameters

## 1.10.2   
 * [Fix]        OPTIONS request double headers error

## 1.10.0
 * [BC break]   Removed deprecated ApiController::initComponent method
 * [BC break]   Removed Component dir and all classes in int Component, ComponentFactory, ComponentInterface
 * [Refactor]   Syrup has now special DB connection 'syrup', while 'default' DB connection should be used in components 

## =====================================================================

## 1.9.12
 * [Feature]    Create index command will firt try to update mapping and if thats not possible it will create new index

## 1.9.11
 * [Fix]        aws-sdk version compatible with storage-api-client

## 1.9.10
 * [Feature]    Changed job return codes and usage of Lock DB

## 1.9.9
 * [Fix]        logData from console exception
 * [Fix]        html mime type set in s3 exception uploader

## 1.9.8
 * [Fix]        Log exception data

## 1.9.7
 * [Feature]    Updated default mapping

## 1.9.6
 * [Fix]        Job object now holds information about index from which it was r

## 1.9.5
 * [Fix]        jobCommand error codes compatible with 1.8.x 
 * [Feature]    update to syrup 1.6.x

## 1.9.4
 * [Fix]        Removed type with component name from jobManager methods

## 1.9.3
 * [Refactor]   Use keboola/php-temp service
 * [Refactor]   Better - HTML - exception stack trace to s3

## 1.9.2
 * [Refactor]   Removed type from mappings - all components now have type 'jobs'

## 1.9.1
 
 * [Fix]        getJobs improved search

## 1.9.0
 * [Refactor]   Only jobs that terminates with InitializationException will be requeued
 * [Feature]    Create index command now assemble and post mappings alongside index creation
 * [Feature]    jobCommand logs when Job is nout found in ES
 * [Fix]        jobManager getJobs method
 * [Fix]        Create index command tiny fixes

## =====================================================================

## 1.8.10       
 * [Fix]        Lock DB stuff
 
## 1.8.9
 * [Fix]        preform lock DB on locks DB specified in parameters not on default DB
 
## 1.8.8
 * [Fix]        Better logging and exceptionId in response in async job

## 1.8.7
 * [Fix]        Removed intentional fatal error

## 1.8.6
 * [Feature]    Updated syrup version in composer

## 1.8.5        
 * [Refactor]   Error handling
 * [Fix]        Updated parameter.yml.dist for travis

## 1.8.4
 * [Fix]        Do not send exception trace to s3 for fatal errors

## 1.8.3
 * [Feature]    Add commands for creating-running job and registering queue credentials to DB

## 1.8.2
 * [Fix]        Do not send exception trace to s3 for fatal errors
 * [Feature]    Improved handling of errors
 

## 1.8.1
 * [Refactor]   Elasticsearch mapping
 * [Feature]    Host and pid params of the job are updated after job is picked from queue for prcoessing by worker

## 1.8.0
 * [Refactor]   SharedSapiService
 * [Fix]        getpid() function replaced to work on windows also
 * [Feature]    Require syrup 1.5.0 with separate db for locks

## =====================================================================

## 1.7.14
 * [Feature]    JobManager refresh index after insert or update
 * [Feature]    Command for creating new ES index

## 1.7.13
 * [Fix]    jobManager return null when job not found

## 1.7.12
 * [Fix]    Job lockname

## 1.7.11
 * [Refactor]   jobManager

## 1.7.10 
 * [Fix]    Job status logging exception
 
## 1.7.9
 * [Fix]    Job error status

## 1.7.8
 * [Fix]    Job hostname

## 1.7.7
 * [Fix]    Job date format

## 1.7.6
 * [Fix]    jobCommand returns correct exit code
 
## 1.7.5
 * [Fix]    Job lockname default

## 1.7.4
 * [Fix]    Small fixes
 
## 1.7.3
 * [Fix]    Job constructor

## 1.7.2
 * [Fix]    Small fixes 

## 1.7.1
 * [Fix]    Job manager update response

## 1.7.0
 * [Feature]    Extended job structure
 * [BC Break]   Removed initComponent from ApiController
 * [Refactor]   Queue Factory

## =====================================================================

## 1.6.5
 * [Fix]    update to elasticsearch 1.2.

## 1.6.4
 * [Fix]    parameters.yml.dist

## 1.6.3
 * [Fix]    s3 uploader

## 1.6.2
 * [Fix]    s3 uploader

## 1.6.1
 * [Fix]    parameters.yml.dist

## 1.6.0 
 
 * [BC Break]   ComponentName is now defined as app_name in parameters.yml
 * [BC Break]   Temp refactord
 * [BC Break]   Encryptor refactord

## =====================================================================

## 1.5.2
 * [Feature]    Added support for asynchronous jobs using elastic search and SQS

## 1.5.1
 * [chore] code style

## 1.5.0
 * [BC break] Updated to SAPI client 2.11.x
 * [BC break] Coding style, underscores from private and protected properties and methods removed

## 1.4.0
 * [refactor]   Removed dev dependency on development-bundle, using Syrup 1.2.x instead
 * [BC break]   Console command exceptions are now handled using listener not by extending Application class
 * [BC break]   Updated to SAPI client 2.10.x

## =====================================================================

## 1.3.14
 * [fix]    cache control headers

## 1.3.13
 * [fix]    getQueue removed from component

## 1.3.12
 * [fix]    removed bc breaking condition in TempServiceFactory

## 1.3.11
 * [Feature]    Logging info message "component start" and "component finish" enhanced with http method and params information

## 1.3.10
 * [Fix]    TempService added clearstatcache to prevent errors when creating tmp run folder

## 1.3.9
 * [Feature]        Added Utitliy class with some common useful functions - converting of encoding and other text transformations

## 1.3.8
 * [Refactoring]    ApplicationException and UserException now accepts a parameter with previous exception

## 1.3.7
 * [Feature]        Encryption class available in container (each component can set an 'encryption_key' it in parameters.yml) read more in documentation
 * [Refactoring]    Added BaseController, which implements basic functionality without StorageApi token requirement

## 1.3.6
 * [Refactoring]    Only 'run' action of a component is now logged into SAPI and SharedSapi
 * [Refactoring]    method getPostJson() added to ApiController so it can be easily used in child classes
 * [Feature]        Added UserException and ApplicationException classes

## 1.3.5
 * [Refactoring]    SharedSapi is now configurable for each component inidvidualy
 * [Refactoring]    Changed structure of Syrup Shared Sapi log

## 1.3.4
 * [Fix]    Better handling of exception codes and logging
 * [Fix]    Configurable SharedSapiService SAPI url

## 1.3.3
 * [Fix]    Fixed JsonFormatter and ControllerListener - so they don't strictly depends on SAPI token

## 1.3.2
 * [Refactoring]    JsonFormatter - StorageApi is now set through DI and some minor changes
 * [Refactoring]    TempService - is now initialized in ApiController and not in Component - component gets this service through container
 * [BC break]       In Service Container "storage_api" key now holds instance of Syrup\ComponentBundle\Service\StorageApi\StorageApiService wich is wrapper service for Storage API Client
 * [Deprecation]    Syrup\ComponentBundle\Filesystem\Temp class is now deprecated and will be removed in 1.4.0 in favor of Syrup\ComponentBundle\Filesystem\TempService

## 1.3.1
 * [Fix]    storageApi -> storage_api in ApiController

## 1.3.0
 * [Feature]        Logging to Shared SAPI
 * [Refactoring]    ApiController - Initialization of services now happens in preExecute() method in favour of custom routing and controllers
 * [BC break]       ApiController::$_storageApi -> ApiController::$storageApi
 * [BC break]       In paramters.yml - storageApi.url renamed to storage_api.url, storageApi.test.url renamed to storage_api.test.url

## =====================================================================

## 1.2.24
 * [Bug Fix] Fixed deleting files in TempService

## 1.2.22 - 1.2.23
 * [Refactoring] TempService now creates temp folder for each request

## 1.2.21
 * [Feature] Component can return a Response object or array

## 1.2.20
 * [Refactoring] TempService getTmpPath() method made public

## 1.2.19
 * [Refactoring] Added StorageApi placeholder to service container

## 1.2.18
 * [Refactoring] Abstract command reworked

## 1.2.17
 * [Bug Fix] Dummy Component name

## 1.2.16
 * [Feature] ComponentCommand - abstract command for components
 * [Feature] Temp Service

## 1.2.15
 * [Bug Fix] Logging - context exception must be an instance of exception

## 1.2.14
 * [Feature] Routing loader - autoloading routes from bundles with apropriate prefix

## 1.2.13
 * [Bug Fix] Removed debug dependency on Provisioning

## 1.2.12
 * [Feature] Added changelog

## 1.2.11
 * [Refactoring] Custom Routing Loader moved here from Syrup

