const e=`# Time units\r
nth = \r
    { $n ->\r
        [1] erste\r
        [2] zweite\r
        [3] dritte\r
        [4] vierte\r
        *[other] {$n}-te\r
    }\r
\r
seconds = \r
    { $n ->\r
        [one] 1 Sekunde\r
        *[other] {$n} Sekunden\r
    }\r
\r
minutes = \r
    { $n ->\r
        [one] 1 Minute\r
        *[other] {$n} Minuten\r
    }\r
\r
hours = \r
    { $n ->\r
        [one] 1 Stunde\r
        *[other] {$n} Stunden\r
    }\r
\r
longdate = DATETIME($date, dateStyle: "long")\r
\r
# Basic UI\r
close = Abschließen\r
loading = Lade...\r
intro = Einführung / Anleitung\r
next = Nächste\r
previous = Vorherige\r
question_n = Frage {$n}\r
# checkme\r
extracredit = Extra-Punkte\r
jumptocontent = Navigation überspringen\r
\r
# Launch section\r
launch-continue_assess = Test fortsetzen\r
launch-retake_assess = Test noch einmal machen\r
launch-start_assess = Test starten\r
launch-timewarning = Dieser Test hat eine Zeitbegrenzung. Wenn Sie den Test starten lässt sich der Zeitablauf nicht mehr unterbrechen. Sind Sie sicher, dass Sie den Test starten sollen?\r
launch-resetmsg = Dozenten: Sie können Ihre Versuche dieses Tests zurücksetzen.\r
launch-doreset = Test zurücksetzen\r
launch-view_as_stu = Wie Student: {$name}\r
# checkme these 3\r
launch-scorelist = Liste der Punkte\r
launch-itemanalysis = Detail-Analyse\r
launch-gblinks = Punkteübersicht Links\r
\r
# Closed section\r
closed-hidden = Dieser Test ist derzeit nicht verfügbar.\r
closed-notyet = Dieser Test ist noch nicht verfügbar. Er wird von {$sd} bis {$ed} verfügbar sein.\r
closed-pastdue = Dieser Test war am {$ed} fällig.\r
closed-pasttime = Die Zeit für diesen test ist abgelaufen.\r
closed-needprereq = Sie haben noch nicht die Voraussetzungen erfüllt, um an diesem Test zu arbeiten.\r
closed-prereqreq = Eine Punktzahl von {$score} für {$name} ist erforderlich.\r
closed-no_attempts = Sie haben alle Versuche für diesen Test verbraucht.\r
closed-latepassn = \r
    { $n ->\r
        [one] Sie können einmal verspätet einreichen.\r
        *[other] Sie können {$n} mal verspätet einreichen.\r
    }\r
closed-latepass_needed = \r
    { $n ->\r
        [one] Sie können bis zum {$date} eine verspätete Einreichung nutzen um diesen Test erneut zu öffnen\r
        *[other] Sie können bis zum {$date} {$n} verspätete Einreichungen nutzen um diesen test erneut zu öffnen\r
    }\r
closed-practice_no_latepass = Dieser Test ist zum unbewerteten Üben geöffnet.\r
closed-practice_w_latepass = Sie können diesen Test auch zum unbewerteten Üben öffnen.\r
closed-will_block_latepass = Wenn Sie dies tun, so können Sie den Test nicht mehr verspätet einreichen.\r
closed-confirm = Sind Sie SICHER dass Sie das tun wollen? Sie können danach nicht mehr verspätet einreichen.\r
closed-can_view_scored = Sie können Ihren bewerteten Test ansehen\r
closed-view_scored = Bewerteten Test ansehen\r
closed-use_latepass = \r
    { $n ->\r
        [one] Verspätetes Einreichen nutzen\r
        *[other] {$n} mal verspätetes Einreichen nutzen\r
    }\r
closed-do_practice = Üben\r
closed-unsubmitted_pastdue = Sie haben im Test einen nicht eingereichten Versuch.\r
closed-unsubmitted_overtime = Sie haben im Test einen nicht eingereichten Versuch dessen Zeitbegrenzung abgelaufen ist.\r
closed-submit_now = Jetzt einreichen\r
closed-exit = Beenden\r
closed-teacher_preview = Dieser Test ist für Studierende nicht geöffnet; Sie können ihn aber als Vorschau ansehen.\r
closed-teacher_preview_button = Test-Vorschau\r
closed-teacher_previewall_button = Dozenten-Ansicht\r
\r
# Due dialog\r
duedialog-due = Frist abgelaufen\r
duedialog-nowdue = Dieser Test ist jetzt fällig.\r
duedialog-byq_unsubmitted = Sie haben noch nicht alle Eingaben zur Bewertung eingereicht.\r
duedialog-bya_unsubmitted = Dieser Versuch des Tests wurde noch nicht zur Bewertung eingereicht.\r
duedialog-submitnow = Zur Bewertung einreichen\r
\r
# Set list\r
setlist-practice = Dieser Test ist eine unbewertete Übung\r
setlist-points_possible = {$pts} mögliche Punkte.\r
setlist-due_at = Fällig am {$date}.\r
setlist-originally_due = Ursprünglich fällig am {$date}.\r
setlist-latepass_used = \r
    { $n ->\r
        [one] Sie haben ein verspätetes Einreichen genutzt.\r
        *[other] Sie haben {$n} mal verspätetes Einreichen genutzt.\r
    }\r
setlist-extension = Ihnen wurde eine Verlängerung gewährt.\r
setlist-penalty = Ein Punktabzug von {$p}% wird angewendet.\r
# checkme 2\r
setlist-penalty_after = Nach dem {$date} wird eine Strafe von {$p}% fällig.\r
setlist-earlybonus = Ein Bonus von {$p}% wird bis zum {$date} gewährt.\r
setlist-take = \r
    { $n ->\r
        [one] Sie können diesen Test einmal machen.\r
        *[other] Sie können diesen Test {$n} mal machen.\r
    }\r
setlist-take_more = \r
    { $n ->\r
        [one] Sie können diesen Test noch einmal machen.\r
        *[other] Sie können diesen Test noch {$n} mal machen.\r
    }\r
setlist-attempt_inprogress = Der Test ist in Bearbeitung.\r
setlist-cur_attempt_n_of = Sie arbeiten im Versuch {$n} von {$nmax}.\r
setlist-keep_highest_q = Der beste Versuch jeder Frage wird gewertet.\r
setlist-keep_highest = Der Versuch mit der höchsten Punktzahl wird gewertet.\r
setlist-keep_average = Die im Mittel erreichte Punktzahl wird gewertet.\r
setlist-keep_last = Der letzte Versuch wird gewertet.\r
setlist-retake_penalty = Beim nächsten Versuch wird ein Punktabzug von {$p}% angewendet.\r
setlist-time_expires = Die Zeitbegrenzung für den Test läuft am {$date} ab.\r
setlist-time_expires_wgrace = Die Zeitbegrenzung für diesen Test läuft am {$date} ab und die Kulanzzeit endet am {$grace}.\r
setlist-time_expired = Die Zeit für den Test ist am {$date} abgelaufen.\r
setlist-time_grace_expires = Die Zeit für den Test ist am {$date} abgelaufen und die Kulanzzeit endet am {$grace}.\r
setlist-timelimit = Zeitbegrenzung: {$time}.\r
# checkme 2\r
setlist-timelimit_wgrace = Zeitbegrenzung: {$time}, mit einer Kulanzzeit von {$grace}.\r
setlist-timelimit_wgrace_penalty = Zeitbegrenzung: {$time}, mit einer Kulanzzeit von {$grace} bei einem Punktabzug von {$penalty}%.\r
setlist-timelimit_extend = Erweitert gegenüber der ursprünglichen Zeit von {$time}.\r
setlist-timelimit_restricted = Abgabe bald fällig. Die Zeit läuft ab bei Fälligkeit, {$due}.\r
# checkme 2\r
setlist-timelimit_wgrace_restricted = Zeitbegrenzung: {$time}, mit einer Kulanzzeit bis {$due}. Bei Nutzung der Kulanzzeit gibt es einen Punktabzug von {$penalty}%.\r
setlist-timelimit_wgrace_restricted_penalty = Zeitbegrenzung: {$time}, mit einer Kulanzzeit bis {$due}.\r
# checkme 2\r
setlist-timelimit_ext = Ihnen wurde eine Verlängerung der Zeitbegrenzung um {$n} Minuten gewährt\r
setlist-timelimit_ext_used = Sie haben eine Zeitverlängerung von {$n} Minuten genutzt\r
setlist-excused = Sie können diesen Test überspringen. Dies ändert nichts an Ihrer Note.\r
# checkme\r
setlist-latepass_needed = \r
    { $n ->\r
        [one] Sie können einen LatePass einlösen, um das Fälligkeitsdatum auf {$date} zu verlängern\r
        *[other] Sie können {$n} LatePasses einlösen, um das Fälligkeitsdatum auf den {$date} zu verlängern\r
    }\r
\r
# Group\r
group-isgroup = Dies ist ein Gruppentest.\r
group-teacher_auto = Für diesen Test können die Studierenden die Mitglieder ihrer Gruppe (bis zu {$n}) selbst wählen.\r
group-teacher_preset = Dieser Test verwendet Gruppen, die der Dozent vorher einrichten muss.\r
group-needpreset = Sie sind kein Mitglied einer Gruppe. Wenden Sie sich an Ihren Dozenten, damit er Sie zu einer Gruppe hinzufügt.\r
group-members = Gruppenmitglieder\r
group-max = max {$n}\r
group-remove = Entfernen\r
group-add = Zur Gruppe hinzufügen:\r
group-select = Auswählen...\r
group-addbutton = Hinzufügen\r
\r
# Password\r
password-requires = Dieser Test erfordert ein Passwort.\r
password-label = Passwort:\r
\r
# Question info\r
qinfo-tryn = Versuch {$n} von {$nmax}\r
qinfo-regenn = Version {$n} von {$nmax}\r
qinfo-tries_remaining = \r
    { $n ->\r
        [one] 1 Versuch für diese Frage übrig\r
        *[other] {$n} Versuche für diese Frage übrig\r
    }\r
qinfo-tries_remaining_range = {$min} bis {$max} Vesuche übrig - je nach Teil - siehe Details\r
qinfo-regens_remaining = \r
    { $n ->\r
        [one] Sie können noch 1 mal eine ähnliche Frage versuchen\r
        *[other] Sie können noch {$n} mal eine ähnliche Frage versuchen\r
    }\r
\r
# Question\r
question-submit = Frage einreichen\r
question-checkans = Antwort prüfen\r
question-saveans = Antwort speichern\r
question-next = Nächste Frage\r
question-submit_seqnext = Teil einreichen\r
question-checkans_seqnext = Teilantwort prüfen\r
question-saveans_seqnext = Nächster Teil\r
question-submit_submitall = Alle Teile einreichen\r
question-checkans_submitall = Alle Teile prüfen\r
question-saveans_submitall = Alle Teile speichern\r
question-withdrawn = Diese Frage wurde vom Dozenten zurückgezogen. Sie brauchen sie nicht zu bearbeiten.\r
question-jump_to_answer = Gehe zur Antwort\r
question-jump_warn = Dies verbraucht alle übrigen Versuche für diese Version der Frage.\r
question-showwork = Hier können Sie Ihre ausführliche Lösung einreichen. Sie können auch ein Foto Ihrer Notizen in diesen Bereich ziehen oder über das Büroklammer-Symbol hochladen.\r
# checkme 5\r
question-showwork_n = Arbeit für Frage {$n}\r
question-uploadwork = Fügen Sie Ihre Arbeit hier als Datei oder Bild an\r
question-uploading = Wird hochgeladen...\r
question-intronext = Wählen Sie zunächst eine Frage aus, indem Sie die Auswahlfunktion oder die Schaltfläche „> Weiter“ oben verwenden.\r
question-firstq = Erste Frage\r
\r
# Header\r
header-score = Punkte: {$pts}/{$poss}\r
header-practicescore = Punkte (Übung): {$pts}/{$poss}\r
header-possible = {$poss} mögliche Punkte\r
header-answered = {$n}/{$tot} beantwortet\r
header-assess_submit = Test einreichen\r
header-done = Fertig\r
header-resources_header = Resourcen\r
header-pts = \r
    { $n ->\r
        [one] 1 Punkt\r
        *[other] {$n} Punkte\r
    }\r
header-details = Details\r
header-warn_unattempted = Es scheint eine unbeantwortete Frage zu geben. Sind Sie sicher, dass Sie jetzt einreichen wollen?\r
header-withdrawn = Frage zurückgezogen\r
# checkme\r
header-use_mq = Formeleditor verwenden\r
header-enable_mq = Formeleditor einschalten\r
header-disable_mq = Formeleditor ausschalten\r
header-work_save = Arbeit speichern\r
header-work_saved = Arbeit gespeichert\r
# checkme\r
header-work_save_avail = Schaltfläche zum Speichern des Fortschritts verfügbar\r
header-work_saving = Speichere...\r
header-confirm_assess_submit = Nach dem Einreichen können Sie Ihre Antworten in dieser Version des Tests nicht mehr ändern. Wollen Sie einreichen?\r
header-confirm_assess_unattempted_submit = Es scheint, Sie haben noch nicht alle Fragen versucht. Nach Abgabe des Tests können Sie Ihre Antworten in dieser Version des Tests nicht mehr ändern. Wollen Sie jetzt wirklich einreichen?\r
header-preview_all = Vorschau aller Fragen für Dozenten\r
\r
# Resource\r
resource-sidebar = In Seitenleiste öffnen\r
resource-newtab = In neuem Tab öffnen\r
\r
# Text\r
text-hide = Text ausblenden\r
text-show = Fragentext anzeigen\r
\r
# Errors\r
error-error = Fehler\r
error-invalid_password = Das eingegebene Passwort ist ungültig\r
error-invalid_aid = Ungültige Test-ID\r
error-no_access = Sie müssen Studierender,Dozent oder Tutor sein um auf diesen Test zuzugreifen\r
error-teacher_only = Sie müssen Dozent sein um dies zu nutzen\r
error-missing_param = Beim API-Zugriff fehlt ein notwendiger Parameter\r
error-not_avail = Dieser Test ist zurzeit nicht verfügbar\r
error-not_ready = Diese Aktion ist für diesen Test zurzeit nicht zulässig\r
error-not_practice = Dieser Test ist nicht mehr im Übungsmodus. Gehen Sie zurück und öffnen Sie den Test noch einmal.\r
error-timelimit_expired = Zeitbegrenzung abgelaufen\r
error-timesup_submitting = Die Zeitbegrenzung ist abgelaufen. Es wird jetzt eingereicht.\r
#checkme 2\r
error-workafter_expired = Die Frist für das Hinzufügen weiterer Arbeiten ist abgelaufen.\r
error-workafter_submitting = Die Frist für das Hinzufügen weiterer Aufgaben ist abgelaufen. Die Daten werden jetzt gespeichert.\r
error-out_of_regens = Keine weiteren Versuche für eine ähnliche Frage möglich\r
error-need_group = Sie können diesen test nicht starten, bevor Sie zu einer Gruppe hinzugefügt wurden\r
error-out_of_attempts = Sie haben alle möglichen Versuche für diesen Test aufgebraucht\r
error-already_submitted = Einreichung nicht akzeptiert. Nachdem der Test hier angezeigt wurde haben Sie ihn woanders eingereicht, Die Fragen, die Sie einreichen wollen, könnten veraltet sein.\r
error-no_active_attempt = Sie haben keinen aktiven Versuch\r
error-no_session = Ihre Sitzung ist abgelaufen. Melden Sie sich erneut an, um fortzusetzen.\r
error-lti_no_session = Ihre Sitzung ist abgelaufen. Bitte gehen Sie zurück in Ihr Lernmanagementsystem und öffnen Sie den Test erneut.\r
error-fast_regen = Hey, versuche in Ruhe die Frage zu bearbeiten bevor Du eine ähnliche Frage versuchst. Warte 5 Sekunden bis zu einem neuen Versuch.\r
error-nochange = Ihre Antworten wurden seit der letzten Einreichung nicht geändert.\r
error-noserver = Die Webseite antwortet nicht\r
error-parseerror = Der Server hat eine ungültige Antwort gesendet\r
error-livepoll_wrongquestion = Die eingereichte Frage ist nicht die aktuelle Frage.\r
error-livepoll_notopen = Keine Einreichung zu dieser Frage möglich.\r
error-need_relaunch = Benötigte Informationen fehlen. Bitte gehen Sie zurück in Ihr Lernmanagementsystem und öffnen Sie den Test erneut.\r
#checkme 4\r
error-ytnotready = YouTube ist noch nicht bereit. Bitte haben Sie einen Moment Geduld.\r
error-file_upload_error = Fehler beim Hochladen der Datei\r
error-file_toolarge = Fehler beim Hochladen der Datei – die Datei ist zu groß. Die Dateigröße darf 15 MB nicht überschreiten.\r
error-file_invalidtype = Fehler beim Hochladen der Datei – ungültiger Dateityp\r
\r
# Confirm\r
confirm-ok = OK\r
confirm-cancel = Abbruch\r
\r
# Score result\r
scoreresult-correct = Richtig\r
scoreresult-incorrect = Falsch\r
scoreresult-partial = Teilweise richtig\r
scoreresult-retry = Frage noch einmal versuchen\r
scoreresult-next = Nächste Frage\r
scoreresult-retryq = Sie können diese Frage unten noch einmal versuchen\r
scoreresult-trysimilar = Eine ähnliche Frage versuchen\r
scoreresult-scorepts = {$pts} von {$poss} Punkten\r
scoreresult-scorelast = Punkte beim letzten Versuch:\r
scoreresult-submitted = Frage eingereicht.\r
scoreresult-see_details = Mehr unter Details.\r
scoreresult-manual_grade = Die Frage enthält Teile, die Ihr Dozent bewerten muss. Bis sie bewertet wurden werden 0 Punkte angezeigt.\r
#checkme 4\r
scoreresult-jumptoincorrect = Springe zum ersten veränderbaren fehlerhaften Teil.\r
scoreresult-jumptolast = Zum zuletzt eingereichten Teil springen\r
scoreresult-allpartscorrect = Alle eingereichten Teile sind korrekt.\r
scoreresult-onepartincorrect = Mindestens ein bewerteter Teil ist falsch.\r
\r
# Sequential Score results\r
#checkme 3\r
seqresult-incorrect = One or more answers were incorrect\r
seqresult-continue = Continue working on this part\r
seqresult-next = Continue to the next part\r
\r
# Summary\r
summary-no_total = Ihr Test wurde eingereicht.\r
#checkme 2\r
summary-viewwork_work = Sie können Ihre Arbeit in der Notenübersicht ansehen.\r
summary-viewwork_work_after = Sie können Ihre Arbeit nach der Fälligkeit des Tests in der Notenübersicht ansehen.\r
summary-viewwork_immediately = Sie können Ihre Arbeit und Ihre Punkte in der Notenübersicht ansehen.\r
summary-viewwork_after_due = Sie können Ihre Arbeit und Ihre Punkte nach der Fälligkeit des Tests in der Notenübersicht ansehen.\r
#checkme 3\r
summary-viewwork_work_scores_after = Sie können Ihre Arbeit im Notenbuch und die Bewertungen nach dem Abgabetermin einsehen.\r
summary-viewwork_work_after_lp = Sie können Ihre Arbeit nach Ablauf der Nachfrist im Notenbuch einsehen.\r
summary-viewwork_after_lp = Sie können Ihre Arbeiten und Noten nach Ablauf der Nachfrist im Notenbuch einsehen.\r
summary-viewwork_never = \r
summary-score = Punkte\r
summary-recordedscore = Gespeicherter Punktestand\r
summary-use_override = Note durch Dozent korrigiert\r
summary-scorepts = {$pts} von {$poss} Punkten\r
summary-retake_penalty = {$n}% Abzug wegen Wiederholung\r
summary-late_penalty = {$n}% Abzug wegen verspätetem Einreichen\r
summary-scorelist = Liste der Punkte\r
summary-reshowquestions = Fragen überprüfen\r
summary-new_excused = Aufgrund Ihrer bisherigen Ergebnisse in diesem Test müssen Sie die folgenden Tests nicht bearbeiten:\r
\r
# Score list\r
scorelist-question = Frage\r
scorelist-score = Punkte\r
scorelist-pts = \r
    { $poss ->\r
        [one] {$pts} von {$poss} Punkt\r
        *[other] {$pts} von {$poss} Punkten\r
    }\r
scorelist-unattempted = Nicht versucht\r
\r
# Category list\r
catlist-category = Kategorie\r
catlist-score = Punkte\r
catlist-pts = \r
    { $poss ->\r
        [one] {$pts} von {$poss} Punkt\r
        *[other] {$pts} von {$poss} Punkten\r
    }\r
\r
# Previous attempts\r
prev-previous_attempts = Voriger Versuch\r
prev-scored_attempts = Bewertete Versuche\r
prev-all_attempts = Alle Versuche\r
prev-date = Datum\r
prev-score = Punkte\r
prev-viewingb = Arbeit in der Notenübersicht ansehen\r
\r
# Penalties\r
penalties-applied = Abzüge angewendet\r
penalties-retry = Abzug für neuen Versuch\r
penalties-regen = Abzug für erneute Durchführung des Tests\r
penalties-trysimilar = Abzug für den Versuch einer ähnlichen Frage\r
penalties-late = Abzug für verspätete Arbeit\r
#checkme\r
penalties-early = Frühbucherbonus\r
penalties-overtime = Abzug für Zeitüberschreitung\r
\r
# Question details\r
qdetails-question_details = Details der Frage\r
qdetails-part = Teil\r
qdetails-lasttry = Resultate des letzten Versuchs:\r
qdetails-score = Punkte\r
qdetails-try = Verbleibende Versuche\r
qdetails-penalties = Abzüge\r
qdetails-category = Kategorie\r
qdetails-gbscore = Gespeicherte Punkte\r
qdetails-bestpractice = Höchste erreichte Punktzahl\r
qdetails-lastscore = Punkte im letzten Versuch\r
qdetails-license = Lizenz\r
#checkme\r
qdetails-extracredit = Diese Frage ist eine Zusatzaufgabe.\r
\r
# Timer\r
timer-hrs = \r
    { $n ->\r
        [one] hr\r
        *[other] hrs\r
    }\r
timer-min = \r
    { $n ->\r
        [one] min\r
        *[other] mins\r
    }\r
timer-overtime = Zeitüberschreitung\r
timer-show = Verbleibende Zeit anzeigen\r
\r
# Help\r
helps-help = Fragenhilfe\r
helps-message_instructor = Nachricht an Dozent\r
helps-post_to_forum = Im Forum schreiben\r
helps-video = Video\r
helps-read = Lesen\r
helps-written_example = Musterlösung\r
\r
# Unload warnings\r
unload-alert = Warnung\r
unload-unsubmitted_questions = Sie haben Antworten eingegeben, aber nicht abgeschickt. Sin Si sicher, dass Sie beenden wollen?\r
unload-unsubmitted_assessment = Sie haben Ihren Test noch nicht zur Bewertung eingereicht. Denken Sie daran, dafür zurückzukommen.\r
unload-unsubmitted_done_assessment = Sie haben alle Fragen versucht, aber haben den Test noch nicht abgeschickt. Denken Sie daran, dafür zurückzukommen.\r
unload-unsubmitted_work = Sie haben noch nicht alles eingereicht.Nicht eingereichte Informationen gehen verloren. Sind Sie sicher, dass Sie den Test verlassen wollen?\r
\r
# Pages\r
pages-next = Nächste Seite\r
\r
# Print\r
print-print_version = Druckversion\r
print-print = Drucken\r
print-hide_text = Einleitung und Text zwischen Fragen ausblenden\r
print-show_text = Einleitung und Text zwischen Fragen einblenden\r
print-hide_qs = Fragen ausblenden\r
print-show_qs = Fragen einblenden\r
\r
# Video cued\r
videocued-start = Starte Video\r
videocued-continue = Video fortsetzen bis {$title}\r
videocued-skipto = Im Video zu {$title} springen\r
\r
# Live poll\r
livepoll-settings = Einstellungen für Live-Umfrage\r
livepoll-show_question_default = Bei erstmaliger Auswahl Fragen auf diesem Bildschirm anzeigen\r
livepoll-show_results_live_default = Eingehende Ergebnisse auf diesem Bildschirm anzeigen\r
livepoll-show_results_after = Ergebnisse auf diesem Bildschirm nach Abschluss der Frage anzeigen\r
livepoll-show_answers_after = Nach Abschluss der Frage automatisch die richtigen Antworten anzeigen\r
livepoll-use_timer = Fragen-Timer verwenden\r
livepoll-seconds = Sekunden\r
livepoll-show_question = Frage auf diesem Bildschirm anzeigen\r
livepoll-show_results = Ergebnisse anzeigen\r
livepoll-show_answers = Richtige Antworten anzeigen\r
livepoll-stucnt = \r
    { $n ->\r
        [0] Keine Studierenden\r
        [one] 1 Studierende(r)\r
        *[other] {$n} Studierende\r
    }\r
livepoll-open_input = Eingabe für Studierende öffnen\r
livepoll-close_input = Eingabe für Studierende schließen\r
livepoll-new_version = Eine ähnliche Frage erzeugen\r
livepoll-waiting = Warte darauf, dass der Dozent eine Frage startet\r
livepoll-numresults = \r
    { $n ->\r
        [one] 1 Ergebnis erhalten\r
        *[other] {$n} Ergebnisse erhalten\r
    }\r
livepoll-answer = Antwort\r
livepoll-frequency = Häufigkeit\r
\r
# LTI\r
lti-more = Mehr Optionen\r
lti-userprefs = Benutzereinstellungen\r
lti-msgs = \r
    { $n ->\r
        [0] Nachrichten\r
        [one] Nachrichten (1 neu)\r
        *[other] Nachrichten ({$n} neu)\r
    }\r
lti-forum = \r
    { $n ->\r
        [0] Forum\r
        [one] Forum (1 neu)\r
        *[other] Forum ({$n} neu)\r
    }\r
lti-use_latepass = Verspätetes Einreichen nutzen\r
\r
# Icons\r
icons-retake = Wiederholte Versuche\r
icons-calendar = Datum\r
icons-retry = Versuche\r
icons-alert = Warnung\r
icons-info = Info\r
icons-timer = Timer\r
icons-lock = Sperren\r
icons-square-check = Prüfen\r
icons-group = Gruppe\r
icons-incorrect = Falsch\r
icons-correct = Correct\r
icons-partial = Teilweise richtig\r
icons-dot = Punkt\r
icons-attempted = Versucht\r
icons-partattempted = Teilweise versucht\r
icons-unattempted = Nicht versucht\r
icons-print = Druck\r
icons-left = Vorherige\r
icons-right = Nächste\r
icons-downarrow = Erweitern\r
icons-file = Datei\r
icons-close = Schließen\r
icons-message = Nachricht\r
icons-forum = Forum\r
icons-video = Video\r
icons-eqned = Formeleditor\r
icons-eqnedoff = Formeleditor ausgeschaltet\r
icons-more = Mehr\r
icons-clipboard = Clipboard\r
icons-rubric = Lernziel\r
icons-none = \r
\r
# Gradebook\r
gradebook-detail_title = Testversuche ansehen\r
gradebook-started = Begonnen\r
gradebook-lastchange = Zuletzt geändert\r
gradebook-time_onscreen = Gesamtzeit der Fragen auf dem Bildschirm\r
gradebook-time_on_version = Verbrauchte Zeit für diese Version\r
gradebook-due = Fällig\r
gradebook-originally_due = Ursprünglich fällig\r
gradebook-make_exception = Ausnahme machen\r
gradebook-edit_exception = Ausnahme bearbeiten\r
gradebook-attempt_n = Versuch {$n}\r
gradebook-version_n = Version {$n}\r
gradebook-scored_attempt = Bewerteter Versuch\r
gradebook-practice_attempt = Übungsversuch\r
gradebook-submitted = Eingereicht\r
gradebook-scored = bewertet\r
gradebook-score = Punkte\r
gradebook-not_started = Nicht begonnen\r
gradebook-not_submitted = Nicht eingereicht\r
gradebook-best_on_question = Die Note wird aufgrund der besten Version jeder Frage berechnet\r
gradebook-keep_best = Die Note wird aufgrund des besten Test-Versuchs berechnet\r
gradebook-keep_avg = Die Note wird aufgrund des Mittels aller Test-Versuche berechnet\r
gradebook-keep_last = Die Note wird aufgrund des letzten Test-Versuchs berechnet\r
gradebook-full_credit_parts = Volle Punkte für alle Teile\r
#checkme\r
gradebook-full_manual_parts = Volle Punktzahl für alle manuell bewerteten Teile.\r
gradebook-full_credit = Volle Punktzahl\r
gradebook-add_feedback = Rückmeldung hinzufügen\r
gradebook-feedback = Rückmeldung\r
#checkme\r
gradebook-feedback_for = Rückmeldung für {$name}\r
gradebook-general_feedback = Allgemeine Rückmeldung\r
gradebook-use_in_msg = Verwendung in Nachricht\r
#checkme\r
gradebook-msg_student = Nachricht an den Schüler senden\r
gradebook-clear_hdr = Bestätigung löschen\r
gradebook-clear_all = Alle Versuche löschen\r
gradebook-clear_attempt = Diesen Versuch löschen\r
gradebook-clear_qwork = Arbeit an dieser Frage löschen\r
gradebook-question_id = Fragen-ID\r
gradebook-seed = Initialwert\r
gradebook-msg_owner = Den Eigentümer über Probleme informieren\r
gradebook-had_help = Hilfe verfügbar\r
gradebook-save = Änderungen speichern\r
#checkme\r
gradebook-savenext = Speichern und zum nächsten Schüler wechseln\r
gradebook-return = Zurück zur Notenübersicht\r
gradebook-gb_score = Punkte in der Notenübersicht\r
gradebook-override = Punkte korrigieren\r
gradebook-overridden = Vom Dozenten korrigiert\r
gradebook-view_as_stu = Als Student ansehen\r
gradebook-print = Druckversion\r
#checkme 2\r
gradebook-filters = Filter und Optionen\r
gradebook-hide = Verstecken\r
gradebook-hide_perfect = Fragen mit voller Punktzahl ausblenden\r
#checkme 5\r
gradebook-hide_100 = Ergebnis ≥ 100 % (nach Abzügen)\r
gradebook-hide_nonzero = 0 < Punktzahl < 100 % (vor Abzügen)\r
gradebook-hide_zero = Punktzahl = 0\r
gradebook-hide_fb = Fragen mit Feedback\r
gradebook-hide_nowork = Fragen ohne Arbeit\r
# checkme\r
gradebook-hide_unans = Unbeantwortete Fragen\r
gradebook-show_unans = Unbeantwortete Fragen einblenden\r
gradebook-quick_grade = Schnell-Bewertung\r
gradebook-saving = Speichere...\r
gradebook-saved = Gespeichert\r
gradebook-save_fail = Fehler beim Speichern\r
gradebook-clear_completely_msg = Alle Versuche des Studierenden löschen, so als ob der Studierende den Test nie gestartet hätte. Wenn der Studierende den Test erneut startet, so erhält er neue Versionen aller Fragen.\r
gradebook-clear_all_work_msg = Die Arbeit aller Studierenden löschen, aber die neuesten Versionen der Fragen beibehalten.\r
gradebook-clear_attempt_regen_msg = Diesen Testversuch vollständig löschen. Wenn die Studierenden den Test erneut versuchen, so erhalten sie neue Versionen aller Fragen.\r
gradebook-clear_attempt_msg = Die Arbeit an diesem Versuch löschen. Der Studierende kann diesen Versuch mit derselben Version der Fragen wiederholen.\r
gradebook-clear_qver_regen_msg = Diese Version der Frage vollständig löschen.\r
gradebook-clear_qver_regen_msg2 = Die Arbeit der Studierenden an dieser Frage löschen und eine neue Version der Frage erzeugen.\r
gradebook-clear_qver_msg = Die Arbeit der Studierenden an dieser Frage löschen, aber die Version der Frage beibehalten.\r
gradebook-clear_warning = WARNUNG: Diese Aktion löscht Daten der Studierenden. Sie KANN NICHT rückgängig gemacht werden.\r
gradebook-unsaved_warn = Warnung: Sie haben ungespeicherte geänderte Rückmeldungen. Wenn Sie die Versionen jetzt ändern, werden diese Änderungen ingnoriert.\r
gradebook-unsubmitted = Der Test-Versuch wurde nicht zur Bewertung eingereicht.\r
gradebook-show_tries = Alle Versuche anzeigen\r
gradebook-show_penalties = Angewandte Abzüge anzeigen\r
gradebook-show_autosaves = Was wurde automatisch gespeichert?\r
gradebook-all_tries = Alle Versuche\r
gradebook-part_n = Teil {$n}\r
gradebook-try_n = Versuch {$n}\r
gradebook-autosaves = Automatisch gespeichert\r
gradebook-autosave_info = Ergebnisse des Studierenden wurden automatisch gespeichert aber nicht vom Studierenden zur Bewertung eingereicht. Sie gehen deshalb nicht in die Bewertung ein.\r
gradebook-autosave_byassess = Automatisch gespeicherte Ergebnisse werden nach Einreichen des Tests bewertet.\r
gradebook-view_edit = Frage ansehen/bearbeiten\r
gradebook-show_all_ans = Alle Antworten anzeigen\r
#checkme\r
gradebook-show_all_work = Alle Arbeitsschritte anzeigen\r
gradebook-no_versions = Noch keine Test-Versuche anzuzeigen\r
gradebook-minutes = Minuten\r
gradebook-avail_never = Die Noten sind zurzeit vom Dozenten nicht freigegeben.\r
#checkme\r
gradebook-avail_manual = Die Note wird derzeit vom Lehrer ausgeblendet.\r
gradebook-avail_after_take = Wird angezeigt, wenn Sie einen Test-Versuch einreichen.\r
gradebook-avail_after_due = Wird nach Fälligkeit angezeigt.\r
#checkme\r
gradebook-avail_after_lp = Wird nach Ablauf der LatePass-Periode angezeigt.\r
gradebook-latepass_blocked_practice = Verspätetes Einreichen ist blockiert, weil der Studierende den Test im Übungs-Modus angesehen hat.\r
#checkme 6\r
gradebook-latepass_blocked_gb = Die Verwendung eines LatePass ist derzeit gesperrt, da der Schüler die Antworten der Bewertung im Notenbuch eingesehen hat.\r
gradebook-latepass_blocked_lpcutoff = Die Verwendung eines LatePass ist derzeit blockiert, da das Ablaufdatum für den LatePass überschritten wurde.\r
gradebook-latepass_blocked_courseend = Die Nutzung eines LatePass ist derzeit blockiert, da das Kursende bereits überschritten ist.\r
gradebook-latepass_blocked_pastdue = Die Verwendung eines LatePass ist derzeit blockiert, da das Fälligkeitsdatum überschritten ist und LatePässe so eingestellt sind, dass sie nur vor dem Fälligkeitsdatum verwendet werden können.\r
gradebook-latepass_blocked_toolate = Die Verwendung eines LatePass ist derzeit blockiert, da der Abgabetermin so weit überschritten ist, dass eine erneute Abgabe mithilfe der erlaubten Anzahl von LatePasses nicht mehr möglich ist.\r
gradebook-latepass_blocked_toofew = Die Verwendung eines LatePass ist derzeit blockiert, da der Student nicht über genügend LatePässe verfügt.\r
gradebook-clear_latepass_block = Block leeren\r
gradebook-showwork = Lösung anzeigen\r
gradebook-hidework = Lösung ausblenden\r
gradebook-show_excused = Tests, die Sie nicht bearbeiten müssen, anzeigen\r
gradebook-hide_excused = Tests, die Sie nicht bearbeiten müssen, ausblenden\r
gradebook-excused_list = Aufgrund der bisherigen Ergebnisse in diesem Test müssen Sie die folgenden Tests nicht bearbeiten:\r
gradebook-show_endmsg = Endnachricht anzeigen\r
gradebook-hide_endmsg = Endnachricht ausblenden\r
#checkme 20 \r
gradebook-has_timeext = Eine Verlängerung der Zeitbegrenzung um {$n} Minuten ist verfügbar.\r
gradebook-used_timeext = Es wurde eine Zeitverlängerung von {$n} Minuten genutzt.\r
gradebook-attemptext = Ausnahme für {$n} zusätzliche Versionen gewährt.\r
gradebook-preview_files = Alle Dateien in der Vorschau anzeigen\r
gradebook-introtexts = Einleitung und Text zwischen den Fragen\r
gradebook-floating_scoreboxes = Schwimmende Punktekästen\r
gradebook-sidebyside = Nebeneinander\r
gradebook-no_edit = Sie befinden sich in der Notenübersicht. Sie können von hier aus keine Antworten bearbeiten oder Fragen einreichen.\r
gradebook-activitylog = Aktivitätsprotokoll\r
gradebook-nextq = Nächste Frage\r
gradebook-prevq = Vorherige Frage\r
gradebook-oneatatime = Einer nach dem anderen\r
gradebook-a11yalt = Zugängliche Alternative\r
gradebook-set_as_last = An das Ende verschieben\r
gradebook-setaslast_warn = Dadurch wird dieser Versuch zum letzten Versuch und somit zum gewerteten Versuch.\r
gradebook-manualstatus0 = Die Note wurde dem Schüler noch nicht mitgeteilt.\r
gradebook-manualbutton0 = Freigabestufe\r
gradebook-manualstatus1 = Die Note wurde dem Schüler mitgeteilt.\r
gradebook-manualbutton1 = Nicht freigegebene Version\r
gradebook-release_on_save = Bewertung nach dem Speichern für den Schüler freigeben.\r
\r
# Work\r
work-add = Lösung hinzufügen\r
work-hide = Lösungseingabe ausblenden\r
work-noquestions = Alle Fragen bearbeitet\r
work-save = Lösung speichern\r
#checkme\r
work-duein = Die Arbeit muss bis zum {$date} eingereicht werden.\r
work-save_continue = Lösung speichern und weiter\r
work-add_prev = Sie können noch Ihre Lösung für den aktuellsten Versuch einreichen\r
#checkme\r
work-remove = Sind Sie sicher, dass Sie diese Datei entfernen möchten?\r
\r
# Regions\r
regions-questions = Fragen und Text\r
regions-q_and_vid = Video und Fragen\r
regions-pagenav = Seiten-Navigation\r
regions-qnav = Fragen-Navigation\r
regions-qvidnav = Video- und Fragen-Navigation\r
regions-aheader = Test-Info\r
\r
# Links\r
# checkme 2\r
links-settings = Einstellungen\r
links-questions = Fragen\r
\r
# LatePass reasons\r
# checkme all\r
latepass-reason0 = Späte Anmeldungen sind nicht aktiviert.\r
latepass-reason2 = LatePasses können nicht verwendet werden, da die Frist für die Nutzung von LatePasses abgelaufen ist.\r
latepass-reason3 = LatePasses können nicht verwendet werden, da das Kursende bereits überschritten ist.\r
latepass-reason4 = LatePässe können nicht verwendet werden, da das Enddatum der Bewertung überschritten ist und LatePässe vor diesem Datum verwendet werden müssen.\r
latepass-reason5 = LatePasses können nicht verwendet werden, da der Abgabetermin für die zulässige Anzahl an LatePasses bereits zu lange überschritten ist, um die Abgabefrist erneut zu verlängern.\r
latepass-reason6 = LatePasses können nicht verwendet werden, da Sie nicht genügend LatePasses besitzen, um diese Bewertung erneut zu öffnen.\r
latepass-reason7 = LatePässe können nicht verwendet werden, da Sie diese Bewertung im Übungsmodus geöffnet haben, und dies die Verwendung von LatePässen blockiert.\r
latepass-reason8 = LatePässe können nicht verwendet werden, da Sie diese Bewertung im Notenbuch bereits eingesehen haben, und dies die Verwendung von LatePässen blockiert.\r
latepass-reason9 = LatePässe können nicht verwendet werden, da Sie keine Versuche mehr übrig haben.`;export{e as default};
