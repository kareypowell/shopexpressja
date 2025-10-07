/*
 * ATTENTION: An "eval-source-map" devtool has been used.
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file with attached SourceMaps in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/js/reports.js":
/*!*********************************!*\
  !*** ./resources/js/reports.js ***!
  \*********************************/
/***/ (() => {

eval("// Simple reports JavaScript for chart initialization\ndocument.addEventListener('DOMContentLoaded', function () {\n  // Auto-refresh functionality\n  var autoRefreshInterval = null;\n  window.addEventListener('startAutoRefresh', function (event) {\n    if (autoRefreshInterval) {\n      clearInterval(autoRefreshInterval);\n    }\n    autoRefreshInterval = setInterval(function () {\n      Livewire.emit('refreshData');\n    }, event.detail.interval || 300000); // Default 5 minutes\n  });\n  window.addEventListener('stopAutoRefresh', function (event) {\n    if (autoRefreshInterval) {\n      clearInterval(autoRefreshInterval);\n      autoRefreshInterval = null;\n    }\n  });\n\n  // Toast notifications are handled globally in base.blade.php\n});//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9yZXNvdXJjZXMvanMvcmVwb3J0cy5qcyIsIm5hbWVzIjpbImRvY3VtZW50IiwiYWRkRXZlbnRMaXN0ZW5lciIsImF1dG9SZWZyZXNoSW50ZXJ2YWwiLCJ3aW5kb3ciLCJldmVudCIsImNsZWFySW50ZXJ2YWwiLCJzZXRJbnRlcnZhbCIsIkxpdmV3aXJlIiwiZW1pdCIsImRldGFpbCIsImludGVydmFsIl0sInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9yZXNvdXJjZXMvanMvcmVwb3J0cy5qcz82NWNlIl0sInNvdXJjZXNDb250ZW50IjpbIi8vIFNpbXBsZSByZXBvcnRzIEphdmFTY3JpcHQgZm9yIGNoYXJ0IGluaXRpYWxpemF0aW9uXG5kb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdET01Db250ZW50TG9hZGVkJywgZnVuY3Rpb24oKSB7XG4gICAgLy8gQXV0by1yZWZyZXNoIGZ1bmN0aW9uYWxpdHlcbiAgICBsZXQgYXV0b1JlZnJlc2hJbnRlcnZhbCA9IG51bGw7XG4gICAgXG4gICAgd2luZG93LmFkZEV2ZW50TGlzdGVuZXIoJ3N0YXJ0QXV0b1JlZnJlc2gnLCBldmVudCA9PiB7XG4gICAgICAgIGlmIChhdXRvUmVmcmVzaEludGVydmFsKSB7XG4gICAgICAgICAgICBjbGVhckludGVydmFsKGF1dG9SZWZyZXNoSW50ZXJ2YWwpO1xuICAgICAgICB9XG4gICAgICAgIFxuICAgICAgICBhdXRvUmVmcmVzaEludGVydmFsID0gc2V0SW50ZXJ2YWwoKCkgPT4ge1xuICAgICAgICAgICAgTGl2ZXdpcmUuZW1pdCgncmVmcmVzaERhdGEnKTtcbiAgICAgICAgfSwgZXZlbnQuZGV0YWlsLmludGVydmFsIHx8IDMwMDAwMCk7IC8vIERlZmF1bHQgNSBtaW51dGVzXG4gICAgfSk7XG4gICAgXG4gICAgd2luZG93LmFkZEV2ZW50TGlzdGVuZXIoJ3N0b3BBdXRvUmVmcmVzaCcsIGV2ZW50ID0+IHtcbiAgICAgICAgaWYgKGF1dG9SZWZyZXNoSW50ZXJ2YWwpIHtcbiAgICAgICAgICAgIGNsZWFySW50ZXJ2YWwoYXV0b1JlZnJlc2hJbnRlcnZhbCk7XG4gICAgICAgICAgICBhdXRvUmVmcmVzaEludGVydmFsID0gbnVsbDtcbiAgICAgICAgfVxuICAgIH0pO1xuICAgIFxuICAgIC8vIFRvYXN0IG5vdGlmaWNhdGlvbnMgYXJlIGhhbmRsZWQgZ2xvYmFsbHkgaW4gYmFzZS5ibGFkZS5waHBcbn0pOyJdLCJtYXBwaW5ncyI6IkFBQUE7QUFDQUEsUUFBUSxDQUFDQyxnQkFBZ0IsQ0FBQyxrQkFBa0IsRUFBRSxZQUFXO0VBQ3JEO0VBQ0EsSUFBSUMsbUJBQW1CLEdBQUcsSUFBSTtFQUU5QkMsTUFBTSxDQUFDRixnQkFBZ0IsQ0FBQyxrQkFBa0IsRUFBRSxVQUFBRyxLQUFLLEVBQUk7SUFDakQsSUFBSUYsbUJBQW1CLEVBQUU7TUFDckJHLGFBQWEsQ0FBQ0gsbUJBQW1CLENBQUM7SUFDdEM7SUFFQUEsbUJBQW1CLEdBQUdJLFdBQVcsQ0FBQyxZQUFNO01BQ3BDQyxRQUFRLENBQUNDLElBQUksQ0FBQyxhQUFhLENBQUM7SUFDaEMsQ0FBQyxFQUFFSixLQUFLLENBQUNLLE1BQU0sQ0FBQ0MsUUFBUSxJQUFJLE1BQU0sQ0FBQyxDQUFDLENBQUM7RUFDekMsQ0FBQyxDQUFDO0VBRUZQLE1BQU0sQ0FBQ0YsZ0JBQWdCLENBQUMsaUJBQWlCLEVBQUUsVUFBQUcsS0FBSyxFQUFJO0lBQ2hELElBQUlGLG1CQUFtQixFQUFFO01BQ3JCRyxhQUFhLENBQUNILG1CQUFtQixDQUFDO01BQ2xDQSxtQkFBbUIsR0FBRyxJQUFJO0lBQzlCO0VBQ0osQ0FBQyxDQUFDOztFQUVGO0FBQ0osQ0FBQyxDQUFDIiwiaWdub3JlTGlzdCI6W119\n//# sourceURL=webpack-internal:///./resources/js/reports.js\n");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval-source-map devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./resources/js/reports.js"]();
/******/ 	
/******/ })()
;