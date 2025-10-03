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

eval("// Simple reports JavaScript for chart initialization\ndocument.addEventListener('DOMContentLoaded', function () {\n  // Auto-refresh functionality\n  var autoRefreshInterval = null;\n  window.addEventListener('startAutoRefresh', function (event) {\n    if (autoRefreshInterval) {\n      clearInterval(autoRefreshInterval);\n    }\n    autoRefreshInterval = setInterval(function () {\n      Livewire.emit('refreshData');\n    }, event.detail.interval || 300000); // Default 5 minutes\n  });\n  window.addEventListener('stopAutoRefresh', function (event) {\n    if (autoRefreshInterval) {\n      clearInterval(autoRefreshInterval);\n      autoRefreshInterval = null;\n    }\n  });\n\n  // Toast notifications\n  window.addEventListener('toastr:success', function (event) {\n    if (typeof toastr !== 'undefined') {\n      toastr.success(event.detail.message);\n    }\n  });\n  window.addEventListener('toastr:error', function (event) {\n    if (typeof toastr !== 'undefined') {\n      toastr.error(event.detail.message);\n    }\n  });\n  window.addEventListener('toastr:info', function (event) {\n    if (typeof toastr !== 'undefined') {\n      toastr.info(event.detail.message);\n    }\n  });\n});//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9yZXNvdXJjZXMvanMvcmVwb3J0cy5qcyIsIm5hbWVzIjpbImRvY3VtZW50IiwiYWRkRXZlbnRMaXN0ZW5lciIsImF1dG9SZWZyZXNoSW50ZXJ2YWwiLCJ3aW5kb3ciLCJldmVudCIsImNsZWFySW50ZXJ2YWwiLCJzZXRJbnRlcnZhbCIsIkxpdmV3aXJlIiwiZW1pdCIsImRldGFpbCIsImludGVydmFsIiwidG9hc3RyIiwic3VjY2VzcyIsIm1lc3NhZ2UiLCJlcnJvciIsImluZm8iXSwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsid2VicGFjazovLy8uL3Jlc291cmNlcy9qcy9yZXBvcnRzLmpzPzY1Y2UiXSwic291cmNlc0NvbnRlbnQiOlsiLy8gU2ltcGxlIHJlcG9ydHMgSmF2YVNjcmlwdCBmb3IgY2hhcnQgaW5pdGlhbGl6YXRpb25cbmRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ0RPTUNvbnRlbnRMb2FkZWQnLCBmdW5jdGlvbigpIHtcbiAgICAvLyBBdXRvLXJlZnJlc2ggZnVuY3Rpb25hbGl0eVxuICAgIGxldCBhdXRvUmVmcmVzaEludGVydmFsID0gbnVsbDtcbiAgICBcbiAgICB3aW5kb3cuYWRkRXZlbnRMaXN0ZW5lcignc3RhcnRBdXRvUmVmcmVzaCcsIGV2ZW50ID0+IHtcbiAgICAgICAgaWYgKGF1dG9SZWZyZXNoSW50ZXJ2YWwpIHtcbiAgICAgICAgICAgIGNsZWFySW50ZXJ2YWwoYXV0b1JlZnJlc2hJbnRlcnZhbCk7XG4gICAgICAgIH1cbiAgICAgICAgXG4gICAgICAgIGF1dG9SZWZyZXNoSW50ZXJ2YWwgPSBzZXRJbnRlcnZhbCgoKSA9PiB7XG4gICAgICAgICAgICBMaXZld2lyZS5lbWl0KCdyZWZyZXNoRGF0YScpO1xuICAgICAgICB9LCBldmVudC5kZXRhaWwuaW50ZXJ2YWwgfHwgMzAwMDAwKTsgLy8gRGVmYXVsdCA1IG1pbnV0ZXNcbiAgICB9KTtcbiAgICBcbiAgICB3aW5kb3cuYWRkRXZlbnRMaXN0ZW5lcignc3RvcEF1dG9SZWZyZXNoJywgZXZlbnQgPT4ge1xuICAgICAgICBpZiAoYXV0b1JlZnJlc2hJbnRlcnZhbCkge1xuICAgICAgICAgICAgY2xlYXJJbnRlcnZhbChhdXRvUmVmcmVzaEludGVydmFsKTtcbiAgICAgICAgICAgIGF1dG9SZWZyZXNoSW50ZXJ2YWwgPSBudWxsO1xuICAgICAgICB9XG4gICAgfSk7XG4gICAgXG4gICAgLy8gVG9hc3Qgbm90aWZpY2F0aW9uc1xuICAgIHdpbmRvdy5hZGRFdmVudExpc3RlbmVyKCd0b2FzdHI6c3VjY2VzcycsIGV2ZW50ID0+IHtcbiAgICAgICAgaWYgKHR5cGVvZiB0b2FzdHIgIT09ICd1bmRlZmluZWQnKSB7XG4gICAgICAgICAgICB0b2FzdHIuc3VjY2VzcyhldmVudC5kZXRhaWwubWVzc2FnZSk7XG4gICAgICAgIH1cbiAgICB9KTtcbiAgICBcbiAgICB3aW5kb3cuYWRkRXZlbnRMaXN0ZW5lcigndG9hc3RyOmVycm9yJywgZXZlbnQgPT4ge1xuICAgICAgICBpZiAodHlwZW9mIHRvYXN0ciAhPT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgICAgICAgIHRvYXN0ci5lcnJvcihldmVudC5kZXRhaWwubWVzc2FnZSk7XG4gICAgICAgIH1cbiAgICB9KTtcbiAgICBcbiAgICB3aW5kb3cuYWRkRXZlbnRMaXN0ZW5lcigndG9hc3RyOmluZm8nLCBldmVudCA9PiB7XG4gICAgICAgIGlmICh0eXBlb2YgdG9hc3RyICE9PSAndW5kZWZpbmVkJykge1xuICAgICAgICAgICAgdG9hc3RyLmluZm8oZXZlbnQuZGV0YWlsLm1lc3NhZ2UpO1xuICAgICAgICB9XG4gICAgfSk7XG59KTsiXSwibWFwcGluZ3MiOiJBQUFBO0FBQ0FBLFFBQVEsQ0FBQ0MsZ0JBQWdCLENBQUMsa0JBQWtCLEVBQUUsWUFBVztFQUNyRDtFQUNBLElBQUlDLG1CQUFtQixHQUFHLElBQUk7RUFFOUJDLE1BQU0sQ0FBQ0YsZ0JBQWdCLENBQUMsa0JBQWtCLEVBQUUsVUFBQUcsS0FBSyxFQUFJO0lBQ2pELElBQUlGLG1CQUFtQixFQUFFO01BQ3JCRyxhQUFhLENBQUNILG1CQUFtQixDQUFDO0lBQ3RDO0lBRUFBLG1CQUFtQixHQUFHSSxXQUFXLENBQUMsWUFBTTtNQUNwQ0MsUUFBUSxDQUFDQyxJQUFJLENBQUMsYUFBYSxDQUFDO0lBQ2hDLENBQUMsRUFBRUosS0FBSyxDQUFDSyxNQUFNLENBQUNDLFFBQVEsSUFBSSxNQUFNLENBQUMsQ0FBQyxDQUFDO0VBQ3pDLENBQUMsQ0FBQztFQUVGUCxNQUFNLENBQUNGLGdCQUFnQixDQUFDLGlCQUFpQixFQUFFLFVBQUFHLEtBQUssRUFBSTtJQUNoRCxJQUFJRixtQkFBbUIsRUFBRTtNQUNyQkcsYUFBYSxDQUFDSCxtQkFBbUIsQ0FBQztNQUNsQ0EsbUJBQW1CLEdBQUcsSUFBSTtJQUM5QjtFQUNKLENBQUMsQ0FBQzs7RUFFRjtFQUNBQyxNQUFNLENBQUNGLGdCQUFnQixDQUFDLGdCQUFnQixFQUFFLFVBQUFHLEtBQUssRUFBSTtJQUMvQyxJQUFJLE9BQU9PLE1BQU0sS0FBSyxXQUFXLEVBQUU7TUFDL0JBLE1BQU0sQ0FBQ0MsT0FBTyxDQUFDUixLQUFLLENBQUNLLE1BQU0sQ0FBQ0ksT0FBTyxDQUFDO0lBQ3hDO0VBQ0osQ0FBQyxDQUFDO0VBRUZWLE1BQU0sQ0FBQ0YsZ0JBQWdCLENBQUMsY0FBYyxFQUFFLFVBQUFHLEtBQUssRUFBSTtJQUM3QyxJQUFJLE9BQU9PLE1BQU0sS0FBSyxXQUFXLEVBQUU7TUFDL0JBLE1BQU0sQ0FBQ0csS0FBSyxDQUFDVixLQUFLLENBQUNLLE1BQU0sQ0FBQ0ksT0FBTyxDQUFDO0lBQ3RDO0VBQ0osQ0FBQyxDQUFDO0VBRUZWLE1BQU0sQ0FBQ0YsZ0JBQWdCLENBQUMsYUFBYSxFQUFFLFVBQUFHLEtBQUssRUFBSTtJQUM1QyxJQUFJLE9BQU9PLE1BQU0sS0FBSyxXQUFXLEVBQUU7TUFDL0JBLE1BQU0sQ0FBQ0ksSUFBSSxDQUFDWCxLQUFLLENBQUNLLE1BQU0sQ0FBQ0ksT0FBTyxDQUFDO0lBQ3JDO0VBQ0osQ0FBQyxDQUFDO0FBQ04sQ0FBQyxDQUFDIiwiaWdub3JlTGlzdCI6W119\n//# sourceURL=webpack-internal:///./resources/js/reports.js\n");

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