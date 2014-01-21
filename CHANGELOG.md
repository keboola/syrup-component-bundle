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

=====================================================================

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

