## Przygotowanie nowej paczki 
1. Przejść do Panelu Admina -> **System** -> **Magento Connect** -> **Spakuj moduły**
2. W przypadku istniejącej już paczki można wybrać ją z **Load Local Package** i przejść do kroku 8
3. **Package Info:**
* Nazwa: `BlueMedia_BluePayment`
* Channel: `community`
* Supported releases: `1.5.0.0 & later`
* Summary: Moduł obsługuje płatności internetowe realizowane przez bramkę płatniczą firmy Blue Media.
* Opis:
```
Narzędzia do obsługi płatności online powstały w oparciu o sprawdzone technologie. Blue Media ma wieloletnie doświadczenie w obsłudze transakcji Internetowych. Za pomocą systemów płatniczych Blue Media zaimplementowanych w serwisach typu eBOK największych dostawców mediów i operatorów komunikacyjnych, regularnie opłaca swoje rachunki ponad 6 mln klientów. Wdrożony w ponad 70 Bankach w Polsce System Płatności BlueCash obsłużył już ponad 2,5 mln transakcji.
Dzięki tym doświadczeniom firma może zaproponować nowe na rynku eCommerce funkcjonalności, z których skorzystają zarówno sklepy jak i ich klienci.
Rozliczenia płatności w czasie rzeczywistym sprawiają, że sklep natychmiast dostaje zapłatę za swoje produkty lub usługi - nie musi "kredytować" zakupu, co w przypadku dużychsklepów i platform eCommerce ma niebagatelne znaczenie.
Model płatności 1:1 księguje poszczególne transakcje osobno - co pozwala na lepszą kontrolę i łatwiejszą obsługę zwrotów. 
Rozwiązania typy Mass Collect umożliwiają pobieranie i zarządzanie płatnościami masowymi i cyklicznymi. Pozwalają oznaczać wpłaty od poszczególnych klientów i dzięki temu skutecznie monitorować płatności.
Obsługa płatności rekurencyjnych - czyli cyklicznych. Mogą być one wykorzystywane przez średniej wielkości wystawców faktur, takich jak uczelnie, wspólnoty mieszkaniowe i inne podmioty, które nie posiadają własnych serwisów typu eBOK. Do tego oferowana jest w wersji white label - może być wizualnie dostosowana do potrzeb Klienta.
Narędzia do obsługi płatności online od Blue Media stworzone są w technologii RWD. Dostosowuje się więc do rozdzielczości kanałów mobilnych, będących przyszłością eCommerce. Opcja zapamiętywania formy ostatniej płatności ułatwia robienie zakupów i zachęca do powrotu do danego sklepu.
https://platnosci.bm.pl/ 
```
* License: @TODO
* License URI: @TODO
4. ** Release Info:**
* Wersja wydania: [aktualna wersja wtyczki]
* Stabilność wydania: `Stable`
* Notatki: [wpisać changelog]
5. **Authors** - dodać autorów
6. **Dependencies:**
* PHP Version Minimum: 5.3.0
* PHP Version Maximum: 5.7.0 (nietestowano na wyższych)
7. **Zawartości:** - tutaj podać wszystkie pliki i/lub foldery dla wtyczki, w tym momencie jest to:
* Magento Community mod: `BlueMedia/BluePayment` (katalog)
* Magento User Interface: `adminhtml/default/default/layout/bluepayment.xml` (plik)
* Magento User Interface: `frontend/base/default/layout/bluepayment.xml` (plik)
* Magento User Interface: `frontend/base/default/template/bluepayment/` (katalog)
* Magento Global Configuration: `modules/BlueMedia_BluePayment.xml` (plik)
* Magento Locale language: `pl_PL/BlueMedia_BluePayment.csv` (plik)
* Magento other: `js/bluepayment` (katalog)
* Magento Theme Skin: `frontend/base/default/images/bluepayment/logo.png` (plik)
8. Kliknąć **Save Data and Create Package**
9. Paczka zostanie przygotowana w `<sciezka_do_magento>/var/connect`
10. Należy zakutalizować instrukcję
11. Można commitować na gitlaba :)
******

Aktualana wersja pluginu jest kompatybilna z Magento w wersji 1.9.3.9