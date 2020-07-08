# OpenSansEmoji

This is basically a mashup font which consists of three fonts. The aim of OpenSansEmoji is to include the whole iOS (currently 6.1) Emoji set while keeping the file size as low as possible. Most of the symbols listed on the ["Emoji" Wikipedia page](http://en.wikipedia.org/w/index.php?title=Emoji&oldid=557685103) are supported. All symbols are in monochrome.

----
## Merged Fonts
* [OpenSans.ttf](http://www.google.com/fonts/specimen/Open+Sans) (Regular) for letters, numbers and punctuation
* [AndroidEmoji.ttf](https://github.com/android/platform_frameworks_base/tree/master/data/fonts) for Emojis < iOS 6
* [Symbola.ttf](http://users.teilar.gr/~g1951d/) for Emojis >= iOS 6

----
## Features
* Fresher and cleaner look of Emojis < iOS 6 by using AndroidEmoji.ttf
* More complete Emoji set for Emojis >= iOS 6 by using Symbola.ttf
* Much smaller file size than Symbola.ttf by skipping non Emoji symbols
* By using OpenSans.tff all latin characters are included, so no fallback font is required
* Smileys which have a typical Android-look were replaced by more neutral smileys

----
## License
Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at
  
[http://www.apache.org/licenses/LICENSE-2.0](http://www.apache.org/licenses/LICENSE-2.0)
  
Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
