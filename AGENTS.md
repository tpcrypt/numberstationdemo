# AGENTS.md - Number Station Demo App Instructions

## Project Goal
Single-file PHP web app simulating Cuban "Atención" numbers station (DGI style, Ana Montes case).  
Interactive demo: encode plaintext → digits (straddling checkerboard) → OTP encrypt → transmit (voice synth) → decode.  
Educational for ham radio club talk on real nation-state espionage crypto.

## Tech Stack & Rules
- Single file: index.php
- Bootstrap 5 via CDN (5.3.3+)
- Pure JavaScript (no frameworks)
- No backend/DB/sessions — use window.* globals to pass data between tabs
- Use Web Speech API for realistic "Atención" voice broadcast
- Exact checkerboard (DGI recovered):
  - Single-digit: 0=N 1=E 3=T 4=A 6=I 7=L 8=S
  - Row 2: 20=B 21=C 22=D 23=F 24=G 25=H 26=J
  - Row 5: 50=K 51=M 52=Ñ 53=O 54=P 55=Q 56=R
  - Row 9: 90=U 91=V 92=W 93=X 94=Y 95=Z
- Ignore non-letters (except Ñ); uppercase only
- Pad digit string with 0s to multiple of 5
- OTP: subtract mod 10 (no borrow); decrypt: add mod 10
- Groups: 5 digits, space-separated
- Default example: "REPORT AGENT STATUS MEET TUESDAY"

## UI Structure
Tabs (Bootstrap nav-tabs):
1. Home: show checkerboard table + load example button
2. Encode: textarea → button → show digit string + groups
3. Encrypt: show digits | OTP input + random gen button | encrypt button → show ciphertext
4. Transmit: show "Atención…" alert + groups + play button (SpeechSynthesis: "Atención… Message number 047. [groups repeated] End of message.")
5. Decode: cipher input + OTP input → decrypt button → show recovered plaintext

## Style Guidelines
- Dark alert for transmission
- Monospace for checkerboard & groups
- Highlight frequent letters
- Responsive, clean, spy-themed minimalism

## Behavior Rules
- Store currentDigits, currentGroups, currentCipher in window
- Auto-switch tabs on load example
- Handle variable message length
- Random OTP: 5-digit groups matching message length
- Voice: rate 0.9, natural pauses with " . " between groups

## Testing Checklist
- Load example → encode → encrypt → transmit (hear voice) → copy cipher+OTP → decode → exact match
- Works offline (after first CDN load)
- No console errors

Execute all programmatic checks above after changes.
