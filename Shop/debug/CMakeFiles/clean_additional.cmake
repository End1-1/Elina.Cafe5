# Additional clean files
cmake_minimum_required(VERSION 3.16)

if("${CONFIG}" STREQUAL "" OR "${CONFIG}" STREQUAL "Release")
  file(REMOVE_RECURSE
  "CMakeFiles\\Shop_net_autogen.dir\\AutogenUsed.txt"
  "CMakeFiles\\Shop_net_autogen.dir\\ParseCache.txt"
  "Shop_net_autogen"
  "qxlsx_build\\CMakeFiles\\QXlsx_autogen.dir\\AutogenUsed.txt"
  "qxlsx_build\\CMakeFiles\\QXlsx_autogen.dir\\ParseCache.txt"
  "qxlsx_build\\QXlsx_autogen"
  )
endif()
