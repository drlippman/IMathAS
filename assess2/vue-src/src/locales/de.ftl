# Time units
nth = 
    { $n ->
        [1] erste
        [2] zweite
        [3] dritte
        [4] vierte
        *[other] {$n}-te
    }

seconds = 
    { $n ->
        [one] 1 Sekunde
        *[other] {$n} Sekunden
    }

minutes = 
    { $n ->
        [one] 1 Minute
        *[other] {$n} Minuten
    }

hours = 
    { $n ->
        [one] 1 Stunde
        *[other] {$n} Stunden
    }

longdate = DATETIME($date, dateStyle: "long")

# Basic UI
close = Abschließen
loading = Lade...
intro = Einführung / Anleitung
next = Nächste
previous = Vorherige
question_n = Frage {$n}
# checkme
extracredit = Extra-Punkte
jumptocontent = Navigation überspringen

# Launch section
launch-continue_assess = Test fortsetzen
launch-retake_assess = Test noch einmal machen
launch-start_assess = Test starten
launch-timewarning = Dieser Test hat eine Zeitbegrenzung. Wenn Sie den Test starten lässt sich der Zeitablauf nicht mehr unterbrechen. Sind Sie sicher, dass Sie den Test starten sollen?
launch-resetmsg = Dozenten: Sie können Ihre Versuche dieses Tests zurücksetzen.
launch-doreset = Test zurücksetzen
launch-view_as_stu = Wie Student: {$name}
# checkme these 3
launch-scorelist = Liste der Punkte
launch-itemanalysis = Detail-Analyse
launch-gblinks = Punkteübersicht Links

# Closed section
closed-hidden = Dieser Test ist derzeit nicht verfügbar.
closed-notyet = Dieser Test ist noch nicht verfügbar. Er wird von {$sd} bis {$ed} verfügbar sein.
closed-pastdue = Dieser Test war am {$ed} fällig.
closed-pasttime = Die Zeit für diesen test ist abgelaufen.
closed-needprereq = Sie haben noch nicht die Voraussetzungen erfüllt, um an diesem Test zu arbeiten.
closed-prereqreq = Eine Punktzahl von {$score} für {$name} ist erforderlich.
closed-no_attempts = Sie haben alle Versuche für diesen Test verbraucht.
closed-latepassn = 
    { $n ->
        [one] Sie können einmal verspätet einreichen.
        *[other] Sie können {$n} mal verspätet einreichen.
    }
closed-latepass_needed = 
    { $n ->
        [one] Sie können bis zum {$date} eine verspätete Einreichung nutzen um diesen Test erneut zu öffnen
        *[other] Sie können bis zum {$date} {$n} verspätete Einreichungen nutzen um diesen test erneut zu öffnen
    }
closed-practice_no_latepass = Dieser Test ist zum unbewerteten Üben geöffnet.
closed-practice_w_latepass = Sie können diesen Test auch zum unbewerteten Üben öffnen.
closed-will_block_latepass = Wenn Sie dies tun, so können Sie den Test nicht mehr verspätet einreichen.
closed-confirm = Sind Sie SICHER dass Sie das tun wollen? Sie können danach nicht mehr verspätet einreichen.
closed-can_view_scored = Sie können Ihren bewerteten Test ansehen
closed-view_scored = Bewerteten Test ansehen
closed-use_latepass = 
    { $n ->
        [one] Verspätetes Einreichen nutzen
        *[other] {$n} mal verspätetes Einreichen nutzen
    }
closed-do_practice = Üben
closed-unsubmitted_pastdue = Sie haben im Test einen nicht eingereichten Versuch.
closed-unsubmitted_overtime = Sie haben im Test einen nicht eingereichten Versuch dessen Zeitbegrenzung abgelaufen ist.
closed-submit_now = Jetzt einreichen
closed-exit = Beenden
closed-teacher_preview = Dieser Test ist für Studierende nicht geöffnet; Sie können ihn aber als Vorschau ansehen.
closed-teacher_preview_button = Test-Vorschau
closed-teacher_previewall_button = Dozenten-Ansicht

# Due dialog
duedialog-due = Frist abgelaufen
duedialog-nowdue = Dieser Test ist jetzt fällig.
duedialog-byq_unsubmitted = Sie haben noch nicht alle Eingaben zur Bewertung eingereicht.
duedialog-bya_unsubmitted = Dieser Versuch des Tests wurde noch nicht zur Bewertung eingereicht.
duedialog-submitnow = Zur Bewertung einreichen

# Set list
setlist-practice = Dieser Test ist eine unbewertete Übung
setlist-points_possible = {$pts} mögliche Punkte.
setlist-due_at = Fällig am {$date}.
setlist-originally_due = Ursprünglich fällig am {$date}.
setlist-latepass_used = 
    { $n ->
        [one] Sie haben ein verspätetes Einreichen genutzt.
        *[other] Sie haben {$n} mal verspätetes Einreichen genutzt.
    }
setlist-extension = Ihnen wurde eine Verlängerung gewährt.
setlist-penalty = Ein Punktabzug von {$p}% wird angewendet.
# checkme 2
setlist-penalty_after = Nach dem {$date} wird eine Strafe von {$p}% fällig.
setlist-earlybonus = Ein Bonus von {$p}% wird bis zum {$date} gewährt.
setlist-take = 
    { $n ->
        [one] Sie können diesen Test einmal machen.
        *[other] Sie können diesen Test {$n} mal machen.
    }
setlist-take_more = 
    { $n ->
        [one] Sie können diesen Test noch einmal machen.
        *[other] Sie können diesen Test noch {$n} mal machen.
    }
setlist-attempt_inprogress = Der Test ist in Bearbeitung.
setlist-cur_attempt_n_of = Sie arbeiten im Versuch {$n} von {$nmax}.
setlist-keep_highest_q = Der beste Versuch jeder Frage wird gewertet.
setlist-keep_highest = Der Versuch mit der höchsten Punktzahl wird gewertet.
setlist-keep_average = Die im Mittel erreichte Punktzahl wird gewertet.
setlist-keep_last = Der letzte Versuch wird gewertet.
setlist-retake_penalty = Beim nächsten Versuch wird ein Punktabzug von {$p}% angewendet.
setlist-time_expires = Die Zeitbegrenzung für den Test läuft am {$date} ab.
setlist-time_expires_wgrace = Die Zeitbegrenzung für diesen Test läuft am {$date} ab und die Kulanzzeit endet am {$grace}.
setlist-time_expired = Die Zeit für den Test ist am {$date} abgelaufen.
setlist-time_grace_expires = Die Zeit für den Test ist am {$date} abgelaufen und die Kulanzzeit endet am {$grace}.
setlist-timelimit = Zeitbegrenzung: {$time}.
# checkme 2
setlist-timelimit_wgrace = Zeitbegrenzung: {$time}, mit einer Kulanzzeit von {$grace}.
setlist-timelimit_wgrace_penalty = Zeitbegrenzung: {$time}, mit einer Kulanzzeit von {$grace} bei einem Punktabzug von {$penalty}%.
setlist-timelimit_extend = Erweitert gegenüber der ursprünglichen Zeit von {$time}.
setlist-timelimit_restricted = Abgabe bald fällig. Die Zeit läuft ab bei Fälligkeit, {$due}.
# checkme 2
setlist-timelimit_wgrace_restricted = Zeitbegrenzung: {$time}, mit einer Kulanzzeit bis {$due}. Bei Nutzung der Kulanzzeit gibt es einen Punktabzug von {$penalty}%.
setlist-timelimit_wgrace_restricted_penalty = Zeitbegrenzung: {$time}, mit einer Kulanzzeit bis {$due}.
# checkme 2
setlist-timelimit_ext = Ihnen wurde eine Verlängerung der Zeitbegrenzung um {$n} Minuten gewährt
setlist-timelimit_ext_used = Sie haben eine Zeitverlängerung von {$n} Minuten genutzt
setlist-excused = Sie können diesen Test überspringen. Dies ändert nichts an Ihrer Note.
# checkme
setlist-latepass_needed = 
    { $n ->
        [one] Sie können einen LatePass einlösen, um das Fälligkeitsdatum auf {$date} zu verlängern
        *[other] Sie können {$n} LatePasses einlösen, um das Fälligkeitsdatum auf den {$date} zu verlängern
    }

# Group
group-isgroup = Dies ist ein Gruppentest.
group-teacher_auto = Für diesen Test können die Studierenden die Mitglieder ihrer Gruppe (bis zu {$n}) selbst wählen.
group-teacher_preset = Dieser Test verwendet Gruppen, die der Dozent vorher einrichten muss.
group-needpreset = Sie sind kein Mitglied einer Gruppe. Wenden Sie sich an Ihren Dozenten, damit er Sie zu einer Gruppe hinzufügt.
group-members = Gruppenmitglieder
group-max = max {$n}
group-remove = Entfernen
group-add = Zur Gruppe hinzufügen:
group-select = Auswählen...
group-addbutton = Hinzufügen

# Password
password-requires = Dieser Test erfordert ein Passwort.
password-label = Passwort:

# Question info
qinfo-tryn = Versuch {$n} von {$nmax}
qinfo-regenn = Version {$n} von {$nmax}
qinfo-tries_remaining = 
    { $n ->
        [one] 1 Versuch für diese Frage übrig
        *[other] {$n} Versuche für diese Frage übrig
    }
qinfo-tries_remaining_range = {$min} bis {$max} Vesuche übrig - je nach Teil - siehe Details
qinfo-regens_remaining = 
    { $n ->
        [one] Sie können noch 1 mal eine ähnliche Frage versuchen
        *[other] Sie können noch {$n} mal eine ähnliche Frage versuchen
    }

# Question
question-submit = Frage einreichen
question-checkans = Antwort prüfen
question-saveans = Antwort speichern
question-next = Nächste Frage
question-submit_seqnext = Teil einreichen
question-checkans_seqnext = Teilantwort prüfen
question-saveans_seqnext = Nächster Teil
question-submit_submitall = Alle Teile einreichen
question-checkans_submitall = Alle Teile prüfen
question-saveans_submitall = Alle Teile speichern
question-withdrawn = Diese Frage wurde vom Dozenten zurückgezogen. Sie brauchen sie nicht zu bearbeiten.
question-jump_to_answer = Gehe zur Antwort
question-jump_warn = Dies verbraucht alle übrigen Versuche für diese Version der Frage.
question-showwork = Hier können Sie Ihre ausführliche Lösung einreichen. Sie können auch ein Foto Ihrer Notizen in diesen Bereich ziehen oder über das Büroklammer-Symbol hochladen.
# checkme 5
question-showwork_n = Arbeit für Frage {$n}
question-uploadwork = Fügen Sie Ihre Arbeit hier als Datei oder Bild an
question-uploading = Wird hochgeladen...
question-intronext = Wählen Sie zunächst eine Frage aus, indem Sie die Auswahlfunktion oder die Schaltfläche „> Weiter“ oben verwenden.
question-firstq = Erste Frage

# Header
header-score = Punkte: {$pts}/{$poss}
header-practicescore = Punkte (Übung): {$pts}/{$poss}
header-possible = {$poss} mögliche Punkte
header-answered = {$n}/{$tot} beantwortet
header-assess_submit = Test einreichen
header-done = Fertig
header-resources_header = Resourcen
header-pts = 
    { $n ->
        [one] 1 Punkt
        *[other] {$n} Punkte
    }
header-details = Details
header-warn_unattempted = Es scheint eine unbeantwortete Frage zu geben. Sind Sie sicher, dass Sie jetzt einreichen wollen?
header-withdrawn = Frage zurückgezogen
# checkme
header-use_mq = Formeleditor verwenden
header-enable_mq = Formeleditor einschalten
header-disable_mq = Formeleditor ausschalten
header-work_save = Arbeit speichern
header-work_saved = Arbeit gespeichert
# checkme
header-work_save_avail = Schaltfläche zum Speichern des Fortschritts verfügbar
header-work_saving = Speichere...
header-confirm_assess_submit = Nach dem Einreichen können Sie Ihre Antworten in dieser Version des Tests nicht mehr ändern. Wollen Sie einreichen?
header-confirm_assess_unattempted_submit = Es scheint, Sie haben noch nicht alle Fragen versucht. Nach Abgabe des Tests können Sie Ihre Antworten in dieser Version des Tests nicht mehr ändern. Wollen Sie jetzt wirklich einreichen?
header-preview_all = Vorschau aller Fragen für Dozenten

# Resource
resource-sidebar = In Seitenleiste öffnen
resource-newtab = In neuem Tab öffnen

# Text
text-hide = Text ausblenden
text-show = Fragentext anzeigen

# Errors
error-error = Fehler
error-invalid_password = Das eingegebene Passwort ist ungültig
error-invalid_aid = Ungültige Test-ID
error-no_access = Sie müssen Studierender,Dozent oder Tutor sein um auf diesen Test zuzugreifen
error-teacher_only = Sie müssen Dozent sein um dies zu nutzen
error-missing_param = Beim API-Zugriff fehlt ein notwendiger Parameter
error-not_avail = Dieser Test ist zurzeit nicht verfügbar
error-not_ready = Diese Aktion ist für diesen Test zurzeit nicht zulässig
error-not_practice = Dieser Test ist nicht mehr im Übungsmodus. Gehen Sie zurück und öffnen Sie den Test noch einmal.
error-timelimit_expired = Zeitbegrenzung abgelaufen
error-timesup_submitting = Die Zeitbegrenzung ist abgelaufen. Es wird jetzt eingereicht.
#checkme 2
error-workafter_expired = Die Frist für das Hinzufügen weiterer Arbeiten ist abgelaufen.
error-workafter_submitting = Die Frist für das Hinzufügen weiterer Aufgaben ist abgelaufen. Die Daten werden jetzt gespeichert.
error-out_of_regens = Keine weiteren Versuche für eine ähnliche Frage möglich
error-need_group = Sie können diesen test nicht starten, bevor Sie zu einer Gruppe hinzugefügt wurden
error-out_of_attempts = Sie haben alle möglichen Versuche für diesen Test aufgebraucht
error-already_submitted = Einreichung nicht akzeptiert. Nachdem der Test hier angezeigt wurde haben Sie ihn woanders eingereicht, Die Fragen, die Sie einreichen wollen, könnten veraltet sein.
error-no_active_attempt = Sie haben keinen aktiven Versuch
error-no_session = Ihre Sitzung ist abgelaufen. Melden Sie sich erneut an, um fortzusetzen.
error-lti_no_session = Ihre Sitzung ist abgelaufen. Bitte gehen Sie zurück in Ihr Lernmanagementsystem und öffnen Sie den Test erneut.
error-fast_regen = Hey, versuche in Ruhe die Frage zu bearbeiten bevor Du eine ähnliche Frage versuchst. Warte 5 Sekunden bis zu einem neuen Versuch.
error-nochange = Ihre Antworten wurden seit der letzten Einreichung nicht geändert.
error-noserver = Die Webseite antwortet nicht
error-parseerror = Der Server hat eine ungültige Antwort gesendet
error-livepoll_wrongquestion = Die eingereichte Frage ist nicht die aktuelle Frage.
error-livepoll_notopen = Keine Einreichung zu dieser Frage möglich.
error-need_relaunch = Benötigte Informationen fehlen. Bitte gehen Sie zurück in Ihr Lernmanagementsystem und öffnen Sie den Test erneut.
#checkme 4
error-ytnotready = YouTube ist noch nicht bereit. Bitte haben Sie einen Moment Geduld.
error-file_upload_error = Fehler beim Hochladen der Datei
error-file_toolarge = Fehler beim Hochladen der Datei – die Datei ist zu groß. Die Dateigröße darf 15 MB nicht überschreiten.
error-file_invalidtype = Fehler beim Hochladen der Datei – ungültiger Dateityp

# Confirm
confirm-ok = OK
confirm-cancel = Abbruch

# Score result
scoreresult-correct = Richtig
scoreresult-incorrect = Falsch
scoreresult-partial = Teilweise richtig
scoreresult-retry = Frage noch einmal versuchen
scoreresult-next = Nächste Frage
scoreresult-retryq = Sie können diese Frage unten noch einmal versuchen
scoreresult-trysimilar = Eine ähnliche Frage versuchen
scoreresult-scorepts = {$pts} von {$poss} Punkten
scoreresult-scorelast = Punkte beim letzten Versuch:
scoreresult-submitted = Frage eingereicht.
scoreresult-see_details = Mehr unter Details.
scoreresult-manual_grade = Die Frage enthält Teile, die Ihr Dozent bewerten muss. Bis sie bewertet wurden werden 0 Punkte angezeigt.
#checkme 4
scoreresult-jumptoincorrect = Springe zum ersten veränderbaren fehlerhaften Teil.
scoreresult-jumptolast = Zum zuletzt eingereichten Teil springen
scoreresult-allpartscorrect = Alle eingereichten Teile sind korrekt.
scoreresult-onepartincorrect = Mindestens ein bewerteter Teil ist falsch.

# Summary
summary-no_total = Ihr Test wurde eingereicht.
#checkme 2
summary-viewwork_work = Sie können Ihre Arbeit in der Notenübersicht ansehen.
summary-viewwork_work_after = Sie können Ihre Arbeit nach der Fälligkeit des Tests in der Notenübersicht ansehen.
summary-viewwork_immediately = Sie können Ihre Arbeit und Ihre Punkte in der Notenübersicht ansehen.
summary-viewwork_after_due = Sie können Ihre Arbeit und Ihre Punkte nach der Fälligkeit des Tests in der Notenübersicht ansehen.
#checkme 3
summary-viewwork_work_scores_after = Sie können Ihre Arbeit im Notenbuch und die Bewertungen nach dem Abgabetermin einsehen.
summary-viewwork_work_after_lp = Sie können Ihre Arbeit nach Ablauf der Nachfrist im Notenbuch einsehen.
summary-viewwork_after_lp = Sie können Ihre Arbeiten und Noten nach Ablauf der Nachfrist im Notenbuch einsehen.
summary-viewwork_never = 
summary-score = Punkte
summary-recordedscore = Gespeicherter Punktestand
summary-use_override = Note durch Dozent korrigiert
summary-scorepts = {$pts} von {$poss} Punkten
summary-retake_penalty = {$n}% Abzug wegen Wiederholung
summary-late_penalty = {$n}% Abzug wegen verspätetem Einreichen
summary-scorelist = Liste der Punkte
summary-reshowquestions = Fragen überprüfen
summary-new_excused = Aufgrund Ihrer bisherigen Ergebnisse in diesem Test müssen Sie die folgenden Tests nicht bearbeiten:

# Score list
scorelist-question = Frage
scorelist-score = Punkte
scorelist-pts = 
    { $poss ->
        [one] {$pts} von {$poss} Punkt
        *[other] {$pts} von {$poss} Punkten
    }
scorelist-unattempted = Nicht versucht

# Category list
catlist-category = Kategorie
catlist-score = Punkte
catlist-pts = 
    { $poss ->
        [one] {$pts} von {$poss} Punkt
        *[other] {$pts} von {$poss} Punkten
    }

# Previous attempts
prev-previous_attempts = Voriger Versuch
prev-scored_attempts = Bewertete Versuche
prev-all_attempts = Alle Versuche
prev-date = Datum
prev-score = Punkte
prev-viewingb = Arbeit in der Notenübersicht ansehen

# Penalties
penalties-applied = Abzüge angewendet
penalties-retry = Abzug für neuen Versuch
penalties-regen = Abzug für erneute Durchführung des Tests
penalties-trysimilar = Abzug für den Versuch einer ähnlichen Frage
penalties-late = Abzug für verspätete Arbeit
#checkme
penalties-early = Frühbucherbonus
penalties-overtime = Abzug für Zeitüberschreitung

# Question details
qdetails-question_details = Details der Frage
qdetails-part = Teil
qdetails-lasttry = Resultate des letzten Versuchs:
qdetails-score = Punkte
qdetails-try = Verbleibende Versuche
qdetails-penalties = Abzüge
qdetails-category = Kategorie
qdetails-gbscore = Gespeicherte Punkte
qdetails-bestpractice = Höchste erreichte Punktzahl
qdetails-lastscore = Punkte im letzten Versuch
qdetails-license = Lizenz
#checkme
qdetails-extracredit = Diese Frage ist eine Zusatzaufgabe.

# Timer
timer-hrs = 
    { $n ->
        [one] hr
        *[other] hrs
    }
timer-min = 
    { $n ->
        [one] min
        *[other] mins
    }
timer-overtime = Zeitüberschreitung
timer-show = Verbleibende Zeit anzeigen

# Help
helps-help = Fragenhilfe
helps-message_instructor = Nachricht an Dozent
helps-post_to_forum = Im Forum schreiben
helps-video = Video
helps-read = Lesen
helps-written_example = Musterlösung

# Unload warnings
unload-alert = Warnung
unload-unsubmitted_questions = Sie haben Antworten eingegeben, aber nicht abgeschickt. Sin Si sicher, dass Sie beenden wollen?
unload-unsubmitted_assessment = Sie haben Ihren Test noch nicht zur Bewertung eingereicht. Denken Sie daran, dafür zurückzukommen.
unload-unsubmitted_done_assessment = Sie haben alle Fragen versucht, aber haben den Test noch nicht abgeschickt. Denken Sie daran, dafür zurückzukommen.
unload-unsubmitted_work = Sie haben noch nicht alles eingereicht.Nicht eingereichte Informationen gehen verloren. Sind Sie sicher, dass Sie den Test verlassen wollen?

# Pages
pages-next = Nächste Seite

# Print
print-print_version = Druckversion
print-print = Drucken
print-hide_text = Einleitung und Text zwischen Fragen ausblenden
print-show_text = Einleitung und Text zwischen Fragen einblenden
print-hide_qs = Fragen ausblenden
print-show_qs = Fragen einblenden

# Video cued
videocued-start = Starte Video
videocued-continue = Video fortsetzen bis {$title}
videocued-skipto = Im Video zu {$title} springen

# Live poll
livepoll-settings = Einstellungen für Live-Umfrage
livepoll-show_question_default = Bei erstmaliger Auswahl Fragen auf diesem Bildschirm anzeigen
livepoll-show_results_live_default = Eingehende Ergebnisse auf diesem Bildschirm anzeigen
livepoll-show_results_after = Ergebnisse auf diesem Bildschirm nach Abschluss der Frage anzeigen
livepoll-show_answers_after = Nach Abschluss der Frage automatisch die richtigen Antworten anzeigen
livepoll-use_timer = Fragen-Timer verwenden
livepoll-seconds = Sekunden
livepoll-show_question = Frage auf diesem Bildschirm anzeigen
livepoll-show_results = Ergebnisse anzeigen
livepoll-show_answers = Richtige Antworten anzeigen
livepoll-stucnt = 
    { $n ->
        [0] Keine Studierenden
        [one] 1 Studierende(r)
        *[other] {$n} Studierende
    }
livepoll-open_input = Eingabe für Studierende öffnen
livepoll-close_input = Eingabe für Studierende schließen
livepoll-new_version = Eine ähnliche Frage erzeugen
livepoll-waiting = Warte darauf, dass der Dozent eine Frage startet
livepoll-numresults = 
    { $n ->
        [one] 1 Ergebnis erhalten
        *[other] {$n} Ergebnisse erhalten
    }
livepoll-answer = Antwort
livepoll-frequency = Häufigkeit

# LTI
lti-more = Mehr Optionen
lti-userprefs = Benutzereinstellungen
lti-msgs = 
    { $n ->
        [0] Nachrichten
        [one] Nachrichten (1 neu)
        *[other] Nachrichten ({$n} neu)
    }
lti-forum = 
    { $n ->
        [0] Forum
        [one] Forum (1 neu)
        *[other] Forum ({$n} neu)
    }
lti-use_latepass = Verspätetes Einreichen nutzen

# Icons
icons-retake = Wiederholte Versuche
icons-calendar = Datum
icons-retry = Versuche
icons-alert = Warnung
icons-info = Info
icons-timer = Timer
icons-lock = Sperren
icons-square-check = Prüfen
icons-group = Gruppe
icons-incorrect = Falsch
icons-correct = Correct
icons-partial = Teilweise richtig
icons-dot = Punkt
icons-attempted = Versucht
icons-partattempted = Teilweise versucht
icons-unattempted = Nicht versucht
icons-print = Druck
icons-left = Vorherige
icons-right = Nächste
icons-downarrow = Erweitern
icons-file = Datei
icons-close = Schließen
icons-message = Nachricht
icons-forum = Forum
icons-video = Video
icons-eqned = Formeleditor
icons-eqnedoff = Formeleditor ausgeschaltet
icons-more = Mehr
icons-clipboard = Clipboard
icons-rubric = Lernziel
icons-none = 

# Gradebook
gradebook-detail_title = Testversuche ansehen
gradebook-started = Begonnen
gradebook-lastchange = Zuletzt geändert
gradebook-time_onscreen = Gesamtzeit der Fragen auf dem Bildschirm
gradebook-time_on_version = Verbrauchte Zeit für diese Version
gradebook-due = Fällig
gradebook-originally_due = Ursprünglich fällig
gradebook-make_exception = Ausnahme machen
gradebook-edit_exception = Ausnahme bearbeiten
gradebook-attempt_n = Versuch {$n}
gradebook-version_n = Version {$n}
gradebook-scored_attempt = Bewerteter Versuch
gradebook-practice_attempt = Übungsversuch
gradebook-submitted = Eingereicht
gradebook-scored = bewertet
gradebook-score = Punkte
gradebook-not_started = Nicht begonnen
gradebook-not_submitted = Nicht eingereicht
gradebook-best_on_question = Die Note wird aufgrund der besten Version jeder Frage berechnet
gradebook-keep_best = Die Note wird aufgrund des besten Test-Versuchs berechnet
gradebook-keep_avg = Die Note wird aufgrund des Mittels aller Test-Versuche berechnet
gradebook-keep_last = Die Note wird aufgrund des letzten Test-Versuchs berechnet
gradebook-full_credit_parts = Volle Punkte für alle Teile
#checkme
gradebook-full_manual_parts = Volle Punktzahl für alle manuell bewerteten Teile.
gradebook-full_credit = Volle Punktzahl
gradebook-add_feedback = Rückmeldung hinzufügen
gradebook-feedback = Rückmeldung
#checkme
gradebook-feedback_for = Rückmeldung für {$name}
gradebook-general_feedback = Allgemeine Rückmeldung
gradebook-use_in_msg = Verwendung in Nachricht
gradebook-clear_hdr = Bestätigung löschen
gradebook-clear_all = Alle Versuche löschen
gradebook-clear_attempt = Diesen Versuch löschen
gradebook-clear_qwork = Arbeit an dieser Frage löschen
gradebook-question_id = Fragen-ID
gradebook-seed = Initialwert
gradebook-msg_owner = Den Eigentümer über Probleme informieren
gradebook-had_help = Hilfe verfügbar
gradebook-save = Änderungen speichern
#checkme
gradebook-savenext = Speichern und zum nächsten Schüler wechseln
gradebook-return = Zurück zur Notenübersicht
gradebook-gb_score = Punkte in der Notenübersicht
gradebook-override = Punkte korrigieren
gradebook-overridden = Vom Dozenten korrigiert
gradebook-view_as_stu = Als Student ansehen
gradebook-print = Druckversion
#checkme 2
gradebook-filters = Filter und Optionen
gradebook-hide = Verstecken
gradebook-hide_perfect = Fragen mit voller Punktzahl ausblenden
#checkme 5
gradebook-hide_100 = Ergebnis ≥ 100 % (nach Abzügen)
gradebook-hide_nonzero = 0 < Punktzahl < 100 % (vor Abzügen)
gradebook-hide_zero = Punktzahl = 0
gradebook-hide_fb = Fragen mit Feedback
gradebook-hide_nowork = Fragen ohne Arbeit
# checkme
gradebook-hide_unans = Unbeantwortete Fragen
gradebook-show_unans = Unbeantwortete Fragen einblenden
gradebook-quick_grade = Schnell-Bewertung
gradebook-saving = Speichere...
gradebook-saved = Gespeichert
gradebook-save_fail = Fehler beim Speichern
gradebook-clear_completely_msg = Alle Versuche des Studierenden löschen, so als ob der Studierende den Test nie gestartet hätte. Wenn der Studierende den Test erneut startet, so erhält er neue Versionen aller Fragen.
gradebook-clear_all_work_msg = Die Arbeit aller Studierenden löschen, aber die neuesten Versionen der Fragen beibehalten.
gradebook-clear_attempt_regen_msg = Diesen Testversuch vollständig löschen. Wenn die Studierenden den Test erneut versuchen, so erhalten sie neue Versionen aller Fragen.
gradebook-clear_attempt_msg = Die Arbeit an diesem Versuch löschen. Der Studierende kann diesen Versuch mit derselben Version der Fragen wiederholen.
gradebook-clear_qver_regen_msg = Diese Version der Frage vollständig löschen.
gradebook-clear_qver_regen_msg2 = Die Arbeit der Studierenden an dieser Frage löschen und eine neue Version der Frage erzeugen.
gradebook-clear_qver_msg = Die Arbeit der Studierenden an dieser Frage löschen, aber die Version der Frage beibehalten.
gradebook-clear_warning = WARNUNG: Diese Aktion löscht Daten der Studierenden. Sie KANN NICHT rückgängig gemacht werden.
gradebook-unsaved_warn = Warnung: Sie haben ungespeicherte geänderte Rückmeldungen. Wenn Sie die Versionen jetzt ändern, werden diese Änderungen ingnoriert.
gradebook-unsubmitted = Der Test-Versuch wurde nicht zur Bewertung eingereicht.
gradebook-show_tries = Alle Versuche anzeigen
gradebook-show_penalties = Angewandte Abzüge anzeigen
gradebook-show_autosaves = Was wurde automatisch gespeichert?
gradebook-all_tries = Alle Versuche
gradebook-part_n = Teil {$n}
gradebook-try_n = Versuch {$n}
gradebook-autosaves = Automatisch gespeichert
gradebook-autosave_info = Ergebnisse des Studierenden wurden automatisch gespeichert aber nicht vom Studierenden zur Bewertung eingereicht. Sie gehen deshalb nicht in die Bewertung ein.
gradebook-autosave_byassess = Automatisch gespeicherte Ergebnisse werden nach Einreichen des Tests bewertet.
gradebook-view_edit = Frage ansehen/bearbeiten
gradebook-show_all_ans = Alle Antworten anzeigen
#checkme
gradebook-show_all_work = Alle Arbeitsschritte anzeigen
gradebook-no_versions = Noch keine Test-Versuche anzuzeigen
gradebook-minutes = Minuten
gradebook-avail_never = Die Noten sind zurzeit vom Dozenten nicht freigegeben.
#checkme
gradebook-avail_manual = Die Note wird derzeit vom Lehrer ausgeblendet.
gradebook-avail_after_take = Wird angezeigt, wenn Sie einen Test-Versuch einreichen.
gradebook-avail_after_due = Wird nach Fälligkeit angezeigt.
#checkme
gradebook-avail_after_lp = Wird nach Ablauf der LatePass-Periode angezeigt.
gradebook-latepass_blocked_practice = Verspätetes Einreichen ist blockiert, weil der Studierende den Test im Übungs-Modus angesehen hat.
#checkme 6
gradebook-latepass_blocked_gb = Die Verwendung eines LatePass ist derzeit gesperrt, da der Schüler die Antworten der Bewertung im Notenbuch eingesehen hat.
gradebook-latepass_blocked_lpcutoff = Die Verwendung eines LatePass ist derzeit blockiert, da das Ablaufdatum für den LatePass überschritten wurde.
gradebook-latepass_blocked_courseend = Die Nutzung eines LatePass ist derzeit blockiert, da das Kursende bereits überschritten ist.
gradebook-latepass_blocked_pastdue = Die Verwendung eines LatePass ist derzeit blockiert, da das Fälligkeitsdatum überschritten ist und LatePässe so eingestellt sind, dass sie nur vor dem Fälligkeitsdatum verwendet werden können.
gradebook-latepass_blocked_toolate = Die Verwendung eines LatePass ist derzeit blockiert, da der Abgabetermin so weit überschritten ist, dass eine erneute Abgabe mithilfe der erlaubten Anzahl von LatePasses nicht mehr möglich ist.
gradebook-latepass_blocked_toofew = Die Verwendung eines LatePass ist derzeit blockiert, da der Student nicht über genügend LatePässe verfügt.
gradebook-clear_latepass_block = Block leeren
gradebook-showwork = Lösung anzeigen
gradebook-hidework = Lösung ausblenden
gradebook-show_excused = Tests, die Sie nicht bearbeiten müssen, anzeigen
gradebook-hide_excused = Tests, die Sie nicht bearbeiten müssen, ausblenden
gradebook-excused_list = Aufgrund der bisherigen Ergebnisse in diesem Test müssen Sie die folgenden Tests nicht bearbeiten:
gradebook-show_endmsg = Endnachricht anzeigen
gradebook-hide_endmsg = Endnachricht ausblenden
#checkme 20 
gradebook-has_timeext = Eine Verlängerung der Zeitbegrenzung um {$n} Minuten ist verfügbar.
gradebook-used_timeext = Es wurde eine Zeitverlängerung von {$n} Minuten genutzt.
gradebook-attemptext = Ausnahme für {$n} zusätzliche Versionen gewährt.
gradebook-preview_files = Alle Dateien in der Vorschau anzeigen
gradebook-introtexts = Einleitung und Text zwischen den Fragen
gradebook-floating_scoreboxes = Schwimmende Punktekästen
gradebook-sidebyside = Nebeneinander
gradebook-no_edit = Sie befinden sich in der Notenübersicht. Sie können von hier aus keine Antworten bearbeiten oder Fragen einreichen.
gradebook-activitylog = Aktivitätsprotokoll
gradebook-nextq = Nächste Frage
gradebook-prevq = Vorherige Frage
gradebook-oneatatime = Einer nach dem anderen
gradebook-a11yalt = Zugängliche Alternative
gradebook-set_as_last = An das Ende verschieben
gradebook-setaslast_warn = Dadurch wird dieser Versuch zum letzten Versuch und somit zum gewerteten Versuch.
gradebook-manualstatus0 = Die Note wurde dem Schüler noch nicht mitgeteilt.
gradebook-manualbutton0 = Freigabestufe
gradebook-manualstatus1 = Die Note wurde dem Schüler mitgeteilt.
gradebook-manualbutton1 = Nicht freigegebene Version
gradebook-release_on_save = Bewertung nach dem Speichern für den Schüler freigeben.

# Work
work-add = Lösung hinzufügen
work-hide = Lösungseingabe ausblenden
work-noquestions = Alle Fragen bearbeitet
work-save = Lösung speichern
#checkme
work-duein = Die Arbeit muss bis zum {$date} eingereicht werden.
work-save_continue = Lösung speichern und weiter
work-add_prev = Sie können noch Ihre Lösung für den aktuellsten Versuch einreichen
#checkme
work-remove = Sind Sie sicher, dass Sie diese Datei entfernen möchten?

# Regions
regions-questions = Fragen und Text
regions-q_and_vid = Video und Fragen
regions-pagenav = Seiten-Navigation
regions-qnav = Fragen-Navigation
regions-qvidnav = Video- und Fragen-Navigation
regions-aheader = Test-Info

# Links
# checkme 2
links-settings = Einstellungen
links-questions = Fragen

# LatePass reasons
# checkme all
latepass-reason0 = Späte Anmeldungen sind nicht aktiviert.
latepass-reason2 = LatePasses können nicht verwendet werden, da die Frist für die Nutzung von LatePasses abgelaufen ist.
latepass-reason3 = LatePasses können nicht verwendet werden, da das Kursende bereits überschritten ist.
latepass-reason4 = LatePässe können nicht verwendet werden, da das Enddatum der Bewertung überschritten ist und LatePässe vor diesem Datum verwendet werden müssen.
latepass-reason5 = LatePasses können nicht verwendet werden, da der Abgabetermin für die zulässige Anzahl an LatePasses bereits zu lange überschritten ist, um die Abgabefrist erneut zu verlängern.
latepass-reason6 = LatePasses können nicht verwendet werden, da Sie nicht genügend LatePasses besitzen, um diese Bewertung erneut zu öffnen.
latepass-reason7 = LatePässe können nicht verwendet werden, da Sie diese Bewertung im Übungsmodus geöffnet haben, und dies die Verwendung von LatePässen blockiert.
latepass-reason8 = LatePässe können nicht verwendet werden, da Sie diese Bewertung im Notenbuch bereits eingesehen haben, und dies die Verwendung von LatePässen blockiert.
latepass-reason9 = LatePässe können nicht verwendet werden, da Sie keine Versuche mehr übrig haben.