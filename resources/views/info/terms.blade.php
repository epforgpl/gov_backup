@extends('layouts.base')

@section('title', 'Regulamin | Archiwum.io')

@section('styles')
    @parent
    <link href="{{ asset('/css/info.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div>
        <div class="page-menu-container">
            <h1>Regulamin</h1>
        </div>
        <div class="row mt-3">
            <div class="col-md-10 offset-1">
                <div id="content">
                    <div class="text-center">
                        <p>REGULAMIN</p>
                        <p>ŚWIADCZENIA USŁUG DROGĄ ELEKTRONICZNĄ</p>
                    </div>
                    <div class="text-center">
                        <p>§ 1</p>
                        <p>POSTANOWIENIA OGÓLNE</p>
                    </div>
                    <ol>
                        <li>Na podstawie art. 8 ust. 1 pkt 1 ustawy z dnia 18 lipca 2002 r. o świadczeniu usług drogą
                            elektroniczną (Dz. U. 2002 nr 144 poz. 1204 ze zm.) Fundacja ePaństwo z siedzibą w Zgorzale
                            ustala regulamin świadczenia usług drogą elektroniczną.
                        </li>
                        <li>Regulamin określa:
                            <ol>
                                <li>rodzaje i zakres usług świadczonych drogą elektroniczną;</li>
                                <li>warunki świadczenia usług drogą elektroniczną;</li>
                                <li>warunki zawierania i rozwiązywania umów o świadczenie usług drogą elektroniczną;
                                </li>
                                <li>tryb postępowania reklamacyjnego w zakresie świadczenia usług drogą elektroniczną.
                                </li>
                            </ol>
                        </li>
                    </ol>
                    <div class="text-center">
                        <p>§ 2</p>
                        <p>DEFINICJE</p>
                    </div>
                    <p>Na potrzeby niniejszego regulaminu wskazane poniżej pojęcia będą miały następujące znaczenie:</p>
                    <ol>
                        <li>Usługodawca - Fundacja ePaństwo z siedzibą w Zgorzale, przy ul. Pliszki 2B/1 05-500
                            Piaseczno, wpisana do rejestru stowarzyszeń, innych organizacji społecznych i zawodowych,
                            fundacji i samodzielnych publicznych zakładów opieki zdrowotnej prowadzonego przez Sąd
                            Rejonowy dla miasta st. Warszawy w Warszawie, XIV Wydział Gospodarczy Krajowego Rejestru
                            Sądowego pod numerem KRS 0000359730 oraz NIP 1231216692;
                        </li>
                        <li>Usługobiorca - każda osoba fizyczna odwiedzająca Serwis, w szczególności każda osoba
                            fizyczna korzystająca z usług udostępnionych przez Usługodawcę w Serwisie;
                        </li>
                        <li>Regulamin - niniejszy regulamin;</li>
                        <li>Serwis - zespół stron internetowych udostępnionych przez Usługodawcę na serwerach
                            internetowych pod adresami:
                            <ol>
                                <li>https://archiwum.io/;</li>
                                <li>https://epf.org.pl/;</li>
                                <li>https://kodujdlapolski.pl/;</li>
                                <li>https://mojepanstwo.pl/;</li>
                                <li>https://mojeprawo.io/;</li>
                                <li>https://pdfcee.pl/;</li>
                                <li>https://rejestr.io/;</li>
                                <li>https://sejmometr.pl/</li>
                            </ol>
                        </li>
                        <li>Świadczenie usług drogą elektroniczną - wykonanie usług świadczonych bez jednoczesnej
                            obecności stron (na odległość), poprzez przekaz danych na indywidualne żądanie
                            usługobiorcy, przesyłanej i otrzymywanej za pomocą urządzeń do elektronicznego
                            przetwarzania, włącznie z kompresją cyfrową, i przechowywania danych, która jest w
                            całości nadawana, odbierana lub transmitowana za pomocą sieci telekomunikacyjnej w
                            rozumieniu ustawy z dnia 16 lipca 2004 r. Prawo telekomunikacyjne;
                        </li>
                    </ol>
                    <div class="text-center">
                        <p>§ 3</p>
                        <p>RODZAJE I ZAKRES USŁUG ŚWIADCZONYCH DROGĄ ELEKTRONICZNĄ</p>
                    </div>
                    <ol>
                        <li>Każdy Usługobiorca jest zobowiązany do przestrzegania Regulaminu od chwili podjęcia
                            czynności zmierzających do skorzystania z usługi udostępnionej w Serwisie. Szczegółowe
                            zasady świadczenia usług mogą określać odrębne regulaminy umieszczone w Serwisie.
                        </li>
                        <li>Usługodawca świadczy drogą elektroniczną następujące usługi:
                            <ol>
                                <li>usługi informacyjne;</li>
                                <li>usługi komunikacyjne;</li>
                                <li>usługi w zakresie rejestracji;</li>
                                <li>usługi w zakresie personalizacji kont,</li>
                                <li>usługi w zakresie korzystania z aplikacji internetowych.</li>
                            </ol>
                        </li>
                        <li>Usługi informacyjne polegają na udostępnianiu na żądanie Usługobiorcy informacji
                            umieszczonych w Serwisie poprzez wyświetlanie strony internetowej o określonym adresie URL,
                            zawierającej dane, indywidualnie żądane przez Usługobiorcę.
                        </li>
                        <li>Usługi komunikacyjne polegają na umożliwieniu na żądanie Usługobiorcy komunikacji z
                            Usługodawcą, bądź innymi Usługobiorcami serwisu. Usługi komunikacyjne mogą być świadczone w
                            szczególności za pośrednictwem formularzy kontaktowych oraz forów dyskusyjnych
                            udostępnianych w ramach Serwisu.
                        </li>
                        <li>Usługi w zakresie rejestracji umożliwiają Usługobiorcy założenie konta w Serwisie poprzez
                            wypełnienie formularza rejestracyjnego udostępnionego przez Usługodawcę w ramach Serwisu.
                            Założenie konta w Serwisie umożliwia Usługobiorcy korzystanie z dodatkowych usług
                            udostępnianych przez Usługodawcę.
                        </li>
                        <li>Usługi w zakresie personalizacji konta umożliwiają zarejestrowanym Usługobiorcom
                            dostosowanie konta do ich indywidualnych preferencji w zakresie korzystania z Serwis        
                        </li>
                        <li>Usługi w zakresie korzystania z aplikacji internetowych dostępnych w Serwisie umożliwiają
                            skorzystanie z funkcji oferowanych w ramach poszczególnych aplikacji.
                        </li>
                    </ol>
                    <div class="text-center">
                        <p>§ 4</p>
                        <p>WARUNKI ŚWIADCZENIA USŁUG DROGĄ ELEKTRONICZNĄ</p>
                    </div>
                    <ol>
                        <li>Wymagania techniczne dotyczące korzystania z Serwisu są następujące:
                            <ol>
                                <li>połączenie z Internetem;</li>
                                <li>przeglądarka internetowa umożliwiająca wyświetlanie na ekranie komputera
                                    dokumentów HTML powiązanych w sieci Internet przez sieciową usługę internetową.
                                </li>
                            </ol>
                        </li>
                        <li>Usługobiorca, aby prawidłowo korzystać z usług Serwisu powinien dysponować sprzętem
                            komputerowym i oprogramowaniem spełniającym poniższe wymogi:
                            <ol>
                                <li>włączona obsługa cookies i Java Script;</li>
                                <li>monitor o rozdzielczości minimum 1240 x 1024;</li>
                                <li>przeglądarka: Firefox, Opera, Chrome, Safari - w wersji co najmniej najnowszej lub
                                    wersji poprzedzającą najnowszą wersję oraz Internet Explorer w wersji co najmniej
                                    9.0.
                                </li>
                            </ol>
                        </li>
                        <li>W przypadku, gdy Usługobiorca nie spełnia warunków wskazanych w pkt 1 i 2 powyżej,
                            Usługodawca nie gwarantuje prawidłowego działania Serwisu oraz zastrzega, że jakość
                            świadczonych w Serwisie usług może ulec obniżeniu.
                        </li>
                        <li>Zakazane jest dostarczanie przez Usługobiorcę treści o charakterze bezprawnym.</li>
                        <li>Zakazane jest podejmowanie przez Usługobiorcę działań mogących wywołać zakłócenia lub
                            uszkodzenia Serwisu.                                  
                        </li>
                    </ol>
                    <div class="text-center">
                        <p>§ 5</p>
                        <p>WARUNKI ZAWIERANIA I ROZWIĄZYWANIA UMÓW O ŚWIADCZENIE USŁUG DROGĄ ELEKTRONICZNĄ</p>
                    </div>
                    <ol>
                        <li>Zawarcie umowy o świadczenie usługi drogą elektroniczną następuje z chwilą rozpoczęcia
                            przez Usługobiorcę korzystania z danej usługi. Korzystanie przez Usługobiorcę z danej
                            usługi odbywa się na zasadach wskazanych w Regulaminie, a w określonych przypadkach
                            również na podstawie szczegółowych zasad świadczenia usług, o których mowa w § 3 pkt 1,
                            zd. 2 Regulaminu, jeśli zostały wprowadzone przez Usługodawcę.
                        </li>
                        <li>Usługobiorca nieposiadający konta w Serwisie może w każdej chwili zakończyć korzystanie
                            z danej usługi. W przypadku opuszczenia przez Usługobiorcę Serwisu, umowa o świadczenie
                            usług drogą elektroniczną rozwiązuje się automatycznie bez konieczności składania
                            dodatkowych oświadczeń stron.
                        </li>
                        <li>Usługobiorca posiadający konto w Serwisie może w każdej chwili zakończyć korzystanie z
                            usługi posiadania konta poprzez skorzystanie z możliwości usunięcia konta.
                        </li>
                    </ol>
                    <div class="text-center">
                        <p>§ 6</p>
                        <p>TRYB POSTĘPOWANIA REKLAMACYJNEGO W ZAKRESIE ŚWIADCZENIA USŁUG DROGĄ ELEKTRONICZNĄ</p>
                    </div>
                    <ol>
                        <li>Usługobiorca ma prawo składać reklamacje w sprawach dotyczących usług udostępnianych w
                            Serwisie.
                        </li>
                        <li>Reklamacje należy składać za pośrednictwem poczty elektronicznej pod adresem e-mail:
                            biuro@epf.org.pl.
                        </li>
                        <li>Usługodawca dołoży wszelkich starań, aby reklamacje były rozpatrzone w terminie 14 dni od
                            ich otrzymania przez Usługodawcę.
                        </li>
                        <li>Prawidłowa reklamacja powinna zawierać: adres e-mail składającego reklamację oraz opis
                            problemu będącego przedmiotem reklamacji.
                        </li>
                        <li>O rezultacie rozpatrzenia reklamacji Usługodawca zawiadomi składającego reklamację, za
                            pośrednictwem poczty elektronicznej wysyłając odpowiedź na adres e-mail podany w
                            zgłoszeniu.
                        </li>
                        <li>Reklamacje niezawierające danych wskazanych w pkt 4 powyżej nie będą rozpatrywane.</li>
                    </ol>
                    <div class="text-center">
                        <p>§ 7</p>
                        <p>POSTANOWIENIA KOŃCOWE</p>
                    </div>
                    <ol>
                        <li>Usługodawca zastrzega sobie prawo do zmiany zawartości Serwisu, ograniczenia dostępności
                            Serwisu oraz wycofania Serwisu.
                        </li>
                        <li>Usługodawca może zablokować dostęp do Serwisu lub jego części z ważnych przyczyn, w tym
                            w szczególności w razie stwierdzenia nieprawidłowości w korzystaniu z Serwisu, lub
                            wystąpienia okoliczności, które mogłyby narazić na szkodę Usługobiorcę lub
                            Usługodawcę.
                        </li>
                        <li>Regulamin może zostać w razie potrzeby aktualizowany. Aktualna wersja Regulaminu została
                            przyjęta i obowiązuje od 3 lipca 2018 r.
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection
