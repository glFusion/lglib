// ** I18N

// Calendar AL language
// Author: Mihai Bazon, <mihai_bazon@yahoo.com>
// Translator: Rigels Gordani rige@hotmail.com
// Updated: Siegfried Gutschi (März 2017) <sigi AT modellbaukalender DOT info>
// Encoding: any
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// full day names
Calendar._DN = new Array
("E Diele",
"E Hene",
"E Marte",
"E Merkure",
"E Enjte",
"E Premte",
"E Shtune",
"E Diele");


// Please note that the following array of short day names (and the same goes
// for short month names, _SMN) isn't absolutely necessary.  We give it here
// for exemplification on how one can customize the short day names, but if
// they are simply the first N letters of the full name you can simply say:
//
//   Calendar._SDN_len = N; // short day name length
//   Calendar._SMN_len = N; // short month name length
//
// If N = 3 then this is not needed either since we assume a value of 3 if not
// present, to be compatible with translation files that were written before
// this feature.

// short day names
Calendar._SDN = new Array
("Die",
"Hen",
"Mar",
"Mer",
"Enj",
"Pre",
"Sht",
"Die");

// First day of the week. "0" means display Sunday first, "1" means display
// Monday first, etc.
Calendar._FD = 0;

// full month names
Calendar._MN = new Array
("Janar",
"Shkurt",
"Mars",
"Prill",
"Maj",
"Qeshor",
"Korrik",
"Gusht",
"Shtator",
"Tetor",
"Nentor",
"Dhjetor");

// short month names
Calendar._SMN = new Array
("Jan",
"Shk",
"Mar",
"Pri",
"Maj",
"Qes",
"Kor",
"Gus",
"Sht",
"Tet",
"Nen",
"Dhj");

// tooltips
Calendar._TT = {};
Calendar._TT["INFO"] = "Per kalendarin";

Calendar._TT["ABOUT"] =
"Zgjedhes i ores/dates ne DHTML \n" +
"\n\n" +"Zgjedhja e Dates:\n" +
"- Perdor butonat \xab, \xbb per te zgjedhur vitin\n" +
"- Perdor  butonat" + String.fromCharCode(0x2039) + ", " + 
String.fromCharCode(0x203a) +
" per te  zgjedhur muajin\n" +
"- Mbani shtypur butonin e mousit per nje zgjedje me te shpejte.";


Calendar._TT["ABOUT_TIME"] = "\n\n" +
"Zgjedhja e kohes:\n" +
"- Kliko tek ndonje nga pjeset e ores per ta rritur ate\n" +
"- ose kliko me Shift per ta zvogeluar ate\n" +
"- ose cliko dhe terhiq per zgjedhje me te shpejte.";

Calendar._TT["PREV_YEAR"] = "Viti i shkuar (prit per menune)";
Calendar._TT["PREV_MONTH"] = "Muaji i shkuar (prit per menune)";
Calendar._TT["GO_TODAY"] = "Sot";
Calendar._TT["NEXT_MONTH"] = "Muaji i ardhshem (prit per menune)";
Calendar._TT["NEXT_YEAR"] = "Viti i ardhshem (prit per menune)";
Calendar._TT["SEL_DATE"] = "Zgjidh daten";
Calendar._TT["DRAG_TO_MOVE"] = "Terhiqe per te levizur";
Calendar._TT["PART_TODAY"] = " (sot)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "Trego te %s te paren";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] = "0,6";

Calendar._TT["CLOSE"] = "Mbyll";
Calendar._TT["TODAY"] = "Sot";
Calendar._TT["TIME_PART"] = "Kliko me (Shift-)ose terhiqe per te ndryshuar vleren";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "%Y-%m-%d";
Calendar._TT["TT_DATE_FORMAT"] = "%a, %b %e";

Calendar._TT["WK"] = "Java";
Calendar._TT["TIME"] = "Koha:";
