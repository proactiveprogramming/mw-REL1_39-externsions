# RegularTooltips  
Provides inline tooltips and popup-based tooltips  
## Installation  
1) <a href = "https://github.com/sanjay-thiyagarajan/RegularTooltips/archive/refs/heads/master.zip">Download</a> the extension and place it in the ```extensions/``` directory.  
2) Add the following line in LocalSettings.php  
```
wfLoadExtension( 'RegularTooltips' );
```  
3) ✅ Done - Navigate to **Special:Version** on your wiki to verify that the extension is successfully installed.  
  
Instead of downloading the zip archive you may also check this extension out via Git:
```
git clone https://github.com/sanjay-thiyagarajan/RegularTooltips.git
```
## Usage  
### 1) Inline tooltip  
```
{{#inline-tooltip: text | tooltip-text}}
```
### 2) Info tooltip  
```
{{#info-tooltip: tooltip-text}}
```  
