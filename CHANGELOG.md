## 1.2.11
 * [Refactoring] Custom Routing Loader moved here from Syrup

## 1.2.12
 * [Feature] Added changelog

## 1.2.13
 * [Bug Fix] Removed debug dependency on Provisioning

## 1.2.14
 * [Feature] Routing loader - autoloading routes from bundles with apropriate prefix

## 1.2.15
 * [Bug Fix] Logging - context exception must be an instance of exception

## 1.2.16
 * [Feature] ComponentCommand - abstract command for components
 * [Feature] Temp Service

## 1.2.17
 * [Bug Fix] Dummy Component name

## 1.2.18
 * [Refactoring] Abstract command reworked

## 1.2.19
 * [Refactoring] Added StorageApi placeholder to service container

## 1.2.20
 * [Refactoring] TempService getTmpPath() method made public

## 1.2.21
 * [Feature] Component can return a Response object or array

## 1.2.22
## 1.2.23
 * [Refactoring] TempService now creates temp folder for each request

## 1.2.24
 * [Bug Fix] Fixed deleting files in TempService

=====================================================================

## 1.3.0
 * [Feature] Logging to Shared SAPI
 * [Refactoring] Initialization of services now happens in ApiController's preExecute() method in favour of custom routing and controllers