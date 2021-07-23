# Instrukcja modułu „BluePayment” dla platformy Magento

## Podstawowe informacje
Moduł płatności umożliwiający realizację transakcji bezgotówkowych w sklepie Magento. UWAGA! Od czerwca 2020 r. firma Adobe nie wspiera już Magento 1.0, co znaczy, że nie są publikowane aktualizacje oraz poprawki dotyczące bezpieczeństwa. Rekomendujemy aktualizację platformy sprzedażowej do wersji 2.0. Wtyczkę Magento 2.0 możesz pobrać [tutaj.](https://github.com/bluepayment-plugin/magento-2.x-plugin/archive/refs/heads/master.zip)

### Główne funkcje
Do najważniejszych funkcji modułu zalicza się:
-	obsługę wielu sklepów jednocześnie z użyciem jednego modułu
-	obsługę zakupów bez rejestracji w serwisie
-	obsługę dwóch trybów działania – testowego i produkcyjnego (dla każdego z nich wymagane są osobne dane kont, o które zwróć się do nas)


### Wymagania
-	Wersja Magento: 1.6.0 – 1.9.0.
-	Wersja PHP zgodna z wymaganiami względem danej wersji sklepu.

## Instalacja
💡 Jeżeli korzystasz z Magento w wersji niższej niż 1.7 – skorzystaj z metody ręcznej instalacji, ponieważ instalacja modułu za pomocą pliku .tgz nie jest obsługiwana.

### Instalacja z użyciem pliku .tgz

1.. Zacznij od [pobrania](https://github.com/bluepayment-plugin/magento-1.x-plugin/archive/refs/heads/master.zip) najnowszej wersji modułu BluePayment dla platformy Magento.
2. Następnie zaloguj się do panelu administracyjnego Magento. 
3. Wybierz z głównego menu **System ➝ Magento Connect ➝ Zarządzanie Magento Connect [Magento Connect Manager]**, a otworzy się nowe okno do administracji modułów Magento.

💡 Zalecamy:
- zaznaczyć opcję Put store on the maintenance mode while installing/upgrading/backup creation;
- utworzyć kopię zapasową, zaznaczając opcję Create Backup.

4. W sekcji Direct package file upload, w punkcie 2. Upload package file, wybierz uprzednio pobrany plik .tgz z modułem BluePayment i kliknij Upload.

![Direct package file upload](https://user-images.githubusercontent.com/87177993/126775927-757d3470-bf73-425b-9b1c-eef69f655950.png)

5. Zobaczysz wtedy sekcję z rezultatem instalacji. Komunikat Package Installed oznacza, że instalacja modułu przebiegła prawidłowo i możesz przejść dalej – do konfiguracji płatności. W przypadku niepowodzenia – zainstaluj moduł ręcznie.

![Rezultat instalacji](https://user-images.githubusercontent.com/87177993/126776310-9126a0b6-260e-4084-9c53-5a3a95875849.png)

### Ręczna instalacja

1. Zacznij od [pobrania](https://github.com/bluepayment-plugin/magento-1.x-plugin/archive/refs/heads/master.zip) najnowszej wersji modułu BluePayment dla platformy Magento.
2. Następnie wgraj plik .tar do katalogu głównego Magento.
3. Będąc w katalogu główny, wykonaj komendę: tar zxvf BlueMedia_BluePayment-*.tgz --exclude package.xml && rm BlueMedia_BluePayment-*.tgz
4. Zaloguj się do panelu administracyjnego Magento i wybierz z głównego menu **System ➝ Zarządzanie cache [Cache Management]**.
5. Kliknij na **Opróżnij składowanie cache Magento [Flush Magento Cache]**.

Po wykonaniu tego kroku wtyczka jesy gotowa do użycia i możesz przejść do jej konfiguracji.

## Konfiguracja
1. Zaloguj się do panelu administracyjnego Magento i wybierz z menu głównego **System ➝ Konfiguracja [Configuration]**. 
2. Następnie, w menu po lewej stronie, wybierz **Sprzedaże [Sales] ➝ Metody płatności [Payment Methods]**.

### Podstawowa konfiguracja modułu
1. Przejdź do strony **Konfiguracja modułu**.
2. Wejdź w zakładkę **Płatności Online BM [Online payment BM]** i wypełnij obowiązkowe pola:
-	**Moduł aktywny [Enabled]**
-	**Tryb testowy [Test mode]**

![Płatności Online BM](https://user-images.githubusercontent.com/87177993/126777578-1ecfa207-fcf7-4483-93b9-fa80bcc8e42b.png)

3. Dla obsługiwanych walut – wypełnij widoczne poniżej pola danymi, które od nas otrzymasz:
-	**Identyfikator serwisu Partnera [Service Partner ID]**
-	**Klucz współdzielony [Shared Key]**

![Obsługa walut](https://user-images.githubusercontent.com/87177993/126777914-641e2464-cd51-4306-988d-a14aa559a4db.png)

### Wyświetlanie kanałów płatności w serwisie

Moduł BluePayment umożliwia klientowi wybór kanału płatności bezpośrednio na stronie sklepu – bez przekierowywania na stronie Blue Media. Żeby aktywować tę funkcję, wykonaj następujące kroki:
1.	Przejdź do strony konfiguracji modułu.
2.	Otwórz zakładkę **Płatności online BM (Online payment BM]** i przy Wyborze kanałów płatności **[Gateway Selection]** zaznacz **Tak [Yes]**.
3.	Jeżeli chcesz, żeby przy kanale płatności było widoczne logo, zaznacz **Tak [Yes]** przy funkcji **Pokaż logo kanałów płatności [Show Gateway Logo]**.

### Przedtransakcja
Usługa przedtransakcji może być użyteczna do:
-	zweryfikowania poprawności parametrów linku płatności, zanim klient zostanie przekierowany na bramkę płatniczą – wywołanie linka powoduje walidację wszystkich parametrów;
-	skrócenia linku płatności – zamiast kilku/kilkunastu parametrów, link zostaje skrócony do dwóch identyfikatorów;
-	ukrycia danych wrażliwych parametrów linku transakcji – sama przedtransakcja następuje w tle, a link do kontynuacji transakcji nie zawiera danych wrażliwych, a jedynie identyfikatory potrzebne do powrotu do transakcji.

### Aktywacja
1.	Żeby aktywować przedtransakcję, przejdź na stronę konfiguracji modułu.
2.	W zakładce **Płatności online BM [Online Payment BM]** zaznacz **Tak [Yes]** przy funkcji **Przedtransakcja [Curl Payment]**.

### Kanały płatności

💡 Żeby skonfigurować kanały płatności, zaloguj się do panelu administracyjnego i wybierz z menu głównego BluePayment ➝ Manage Bluegateways.
Odświeżenie listy kanałów płatności

1.	Wybierz z menu głównego **BluePayment ➝ Manage Bluegateways**.
2.	Następnie kliknij **Sync Gateways** po prawej stronie ekranu.

<img width="472" alt="Sync Gateways" src="https://user-images.githubusercontent.com/87177993/126781311-825e3870-a4c8-4de1-b931-05eaf19ae09d.png">

### Aktywacja i edycja kanału płatności
1.	Wybierz z menu głównego **BluePayment ➝ Manage Bluegateways**.
2.	Następnie kliknij na wiersz kanału, który chcesz aktywować.
3.	Zmień **Status kanału (Gateway Status]** na **Aktywny [Enabled]**.

### Pozostałe opcje dostępne w zakładce kanału to:
-	Identyfikator kanału [Gateway ID]
-	Waluta [Currency]
-	Nazwa banku [Bank Name]
-	Nazwa [Gateway Name] – domyślna nazwa kanału płatności
-	Własna nazwa [Own Name] – umożliwia zmianę nazwy wyświetlanej przy kanale

![Karta płatnicza](https://user-images.githubusercontent.com/87177993/126782086-6bdc8613-0219-4951-b8c9-bd09e2841b64.png)

-	Typ [Gateway Type]
-	Opis [Gateway Description] – opis kanału płatności niewidoczny dla użytkownika
-	Traktuj jako osobną metodę płatności [Is separated method?] – powoduje wyświetlanie danego kanału jako osobnej metody płatności na stronie

<img width="716" alt="Osobne metody płatności" src="https://user-images.githubusercontent.com/87177993/126782280-4848752d-5f39-4745-ba62-d7efcb09bb93.png">
