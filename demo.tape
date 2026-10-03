# Records art/install.gif of the install command.
# Needs VHS: brew install vhs
# Run from your playground app: vhs demo.tape

Output art/install.gif

Set FontSize 16
Set Width 1100
Set Height 720
Set Theme "Catppuccin Mocha"
Set TypingSpeed 40ms

Hide
Type "rm -f config/agent-loops.php && clear"
Enter
Show

Type "php artisan agent-loops:install"
Sleep 400ms
Enter
Sleep 2s
Type "no"
Enter
Sleep 3s
