<?php
/**
 * Accessibility statement templates as core block markup. Pure: no database, no hooks.
 *
 * The Polish template follows "Warunki techniczne publikacji oraz struktura dokumentu
 * elektronicznego Deklaracji Dostępności" (gov.pl) word for word where it prescribes a
 * formula, so it is not translatable. See README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\AccessibilityStatementPage;

defined( 'ABSPATH' ) || exit;

const POLISH_ACT = 'z dnia 4 kwietnia 2019 r. o dostępności cyfrowej stron internetowych i aplikacji mobilnych podmiotów publicznych';

/**
 * A core/paragraph block.
 *
 * @param string $html   Escaped inner HTML.
 * @param string $anchor Optional id (block "anchor").
 * @return string
 */
function block_paragraph( string $html, string $anchor = '' ): string {
	$json = '' === $anchor ? '' : ' {"anchor":"' . \esc_attr( $anchor ) . '"}';
	$id   = '' === $anchor ? '' : ' id="' . \esc_attr( $anchor ) . '"';

	return "<!-- wp:paragraph{$json} -->\n<p{$id}>{$html}</p>\n<!-- /wp:paragraph -->\n\n";
}

/**
 * A core/heading block.
 *
 * @param string $text   Escaped heading text.
 * @param int    $level  Heading level.
 * @param string $anchor Optional id (block "anchor").
 * @return string
 */
function block_heading( string $text, int $level = 2, string $anchor = '' ): string {
	$attrs = array();

	if ( 2 !== $level ) {
		$attrs[] = '"level":' . $level;
	}

	if ( '' !== $anchor ) {
		$attrs[] = '"anchor":"' . \esc_attr( $anchor ) . '"';
	}

	$json = $attrs ? ' {' . \implode( ',', $attrs ) . '}' : '';
	$id   = '' === $anchor ? '' : ' id="' . \esc_attr( $anchor ) . '"';

	return "<!-- wp:heading{$json} -->\n<h{$level} class=\"wp-block-heading\"{$id}>{$text}</h{$level}>\n<!-- /wp:heading -->\n\n";
}

/**
 * A core/list block of core/list-item blocks.
 *
 * @param string[] $items Escaped item HTML.
 * @return string
 */
function block_list( array $items ): string {
	$html = '';

	foreach ( $items as $item ) {
		$html .= "<!-- wp:list-item -->\n<li>{$item}</li>\n<!-- /wp:list-item -->\n\n";
	}

	return "<!-- wp:list -->\n<ul class=\"wp-block-list\">{$html}</ul>\n<!-- /wp:list -->\n\n";
}

/**
 * An inline element carrying one of the gov.pl identifiers.
 *
 * @param string $id   Identifier, e.g. `a11y-podmiot`.
 * @param string $html Escaped content.
 * @return string
 */
function span( string $id, string $html ): string {
	return '<span id="' . \esc_attr( $id ) . '">' . $html . '</span>';
}

/**
 * Normalize the data a template is filled with.
 *
 * @param array $data Keys: entity, site_name, url, date (Y-m-d).
 * @return array{entity:string,site_name:string,url:string,date:string}
 */
function template_data( array $data ): array {
	$string = static fn( $value ): string => \is_string( $value ) ? \trim( $value ) : '';

	$date = $string( $data['date'] ?? '' );

	return array(
		'entity'    => $string( $data['entity'] ?? '' ),
		'site_name' => $string( $data['site_name'] ?? '' ),
		'url'       => $string( $data['url'] ?? '' ),
		'date'      => 1 === \preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '',
	);
}

/**
 * Polish public-sector declaration (deklaracja dostępności) for a website.
 *
 * @param array $data See template_data().
 * @return string Block markup.
 */
function template_pl( array $data ): string {
	$data = template_data( $data );
	$todo = static fn( string $what ): string => \esc_html( '[UZUPEŁNIJ: ' . $what . ']' );

	$entity = '' !== $data['entity'] ? \esc_html( $data['entity'] ) : $todo( 'nazwa podmiotu publicznego' );
	$name   = '' !== $data['site_name'] ? \esc_html( $data['site_name'] ) : $todo( 'nazwa strony internetowej' );
	$date   = '' !== $data['date'] ? \esc_html( $data['date'] ) : $todo( 'rrrr-mm-dd' );
	$url    = '' !== $data['url']
		? '<a id="a11y-url" href="' . \esc_url( $data['url'] ) . '">' . $name . '</a>'
		: span( 'a11y-url', $name );

	$markup = block_paragraph( $todo( 'zastąp wszystkie fragmenty w nawiasach kwadratowych i usuń te uwagi przed publikacją. Daty wpisuj w formacie rrrr-mm-dd. Deklarację aktualizuje się co najmniej raz w roku.' ) );

	$markup .= block_paragraph(
		span( 'a11y-podmiot', $entity ) . ' zobowiązuje się zapewnić dostępność swojej strony internetowej zgodnie z przepisami ustawy ' . POLISH_ACT . '. Oświadczenie w sprawie dostępności ma zastosowanie do strony internetowej ' . $url . '.',
		'a11y-wstep'
	);

	$markup .= block_paragraph(
		'Data publikacji strony internetowej: ' . span( 'a11y-data-publikacja', $todo( 'rrrr-mm-dd' ) ) . '. Data ostatniej istotnej aktualizacji: ' . span( 'a11y-data-aktualizacja', $todo( 'rrrr-mm-dd' ) ) . '.'
	);

	$markup .= block_heading( 'Status pod względem zgodności z ustawą' );
	$markup .= block_paragraph(
		'Strona internetowa jest częściowo zgodna z ustawą ' . POLISH_ACT . ' z powodu niezgodności lub wyłączeń wymienionych poniżej.',
		'a11y-status'
	);
	$markup .= block_paragraph( $todo( 'zostaw jeden z trzech statusów w dokładnym brzmieniu: „Strona internetowa jest zgodna z ustawą…”, „…jest częściowo zgodna z ustawą… z powodu niezgodności lub wyłączeń wymienionych poniżej.” albo „…jest niezgodna z ustawą… Niezgodność (niezgodności) lub wyłączenia wymieniono poniżej.”. Przy statusie „zgodna” usuń obie listy poniżej.' ) );

	$markup .= block_heading( 'Niedostępne treści', 3 );
	$markup .= block_list(
		array(
			$todo( 'wymaganie, które nie zostało spełnione, np. filmy nie posiadają napisów dla osób głuchych' ),
			$todo( 'np. formularz kontaktowy nie posiada dowiązanych etykiet tekstowych' ),
		)
	);

	$markup .= block_heading( 'Wyłączenia', 3 );
	$markup .= block_list(
		array(
			$todo( 'powód wyłączenia, np. filmy zostały opublikowane przed wejściem w życie ustawy o dostępności cyfrowej, mapy są wyłączone z obowiązku zapewniania dostępności' ),
		)
	);
	$markup .= block_paragraph( $todo( 'jeśli powodem jest nadmierne obciążenie, dodaj link do zatwierdzonej przez kierownika jednostki analizy (art. 8 ust. 3 ustawy) z identyfikatorem a11y-ocena.' ) );

	$markup .= block_heading( 'Data sporządzenia i metoda oceny dostępności cyfrowej' );
	$markup .= block_paragraph(
		'Oświadczenie sporządzono dnia: ' . span( 'a11y-data-sporzadzenie', $date ) . '. Deklarację sporządzono na podstawie samooceny przeprowadzonej przez podmiot publiczny.'
	);
	$markup .= block_paragraph( $todo( 'jeśli badanie przeprowadził podmiot zewnętrzny, zastąp drugie zdanie formułą „Deklarację sporządzono na podstawie badania przeprowadzonego przez podmiot zewnętrzny: nazwa podmiotu” i oznacz nazwę identyfikatorem a11y-audytor.' ) );

	$markup .= block_heading( 'Skróty klawiaturowe' );
	$markup .= block_paragraph( 'Na stronie internetowej można używać standardowych skrótów klawiaturowych przeglądarki.' );

	$markup .= block_heading( 'Informacje zwrotne i dane kontaktowe', 2, 'a11y-kontakt' );
	$markup .= block_paragraph(
		'W przypadku problemów z dostępnością strony internetowej prosimy o kontakt. Osobą kontaktową jest ' . span( 'a11y-osoba', $todo( 'imię i nazwisko' ) ) . ', ' . span( 'a11y-email', $todo( 'adres e-mail' ) ) . '. Kontaktować można się także dzwoniąc na numer telefonu ' . span( 'a11y-telefon', $todo( 'numer telefonu' ) ) . '. Tą samą drogą można składać wnioski o udostępnienie informacji niedostępnej oraz składać żądania zapewnienia dostępności.'
	);

	$markup .= block_heading( 'Procedura wnioskowo-skargowa', 3 );
	$markup .= block_paragraph(
		'Każdy ma prawo do wystąpienia z żądaniem zapewnienia dostępności cyfrowej strony internetowej, aplikacji mobilnej lub jakiegoś ich elementu. Można także zażądać udostępnienia informacji za pomocą alternatywnego sposobu dostępu, na przykład przez odczytanie niedostępnego cyfrowo dokumentu, opisanie zawartości filmu bez audiodeskrypcji itp. Żądanie powinno zawierać dane osoby zgłaszającej żądanie, wskazanie, o którą stronę internetową lub aplikację mobilną chodzi oraz sposób kontaktu. Jeżeli osoba żądająca zgłasza potrzebę otrzymania informacji za pomocą alternatywnego sposobu dostępu, powinna także określić dogodny dla niej sposób przedstawienia tej informacji. Podmiot publiczny powinien zrealizować żądanie niezwłocznie, nie później niż w ciągu 7 dni od dnia wystąpienia z żądaniem. Jeżeli dotrzymanie tego terminu nie jest możliwe, podmiot publiczny niezwłocznie informuje o tym wnoszącego żądanie, kiedy realizacja żądania będzie możliwa, przy czym termin ten nie może być dłuższy niż 2 miesiące od dnia wystąpienia z żądaniem. Jeżeli zapewnienie dostępności cyfrowej nie jest możliwe, podmiot publiczny może zaproponować alternatywny sposób dostępu do informacji. W przypadku, gdy podmiot publiczny odmówi realizacji żądania zapewnienia dostępności lub alternatywnego sposobu dostępu do informacji, wnoszący żądanie może złożyć skargę w sprawie zapewniania dostępności cyfrowej strony internetowej, aplikacji mobilnej lub elementu strony internetowej lub aplikacji mobilnej. Po wyczerpaniu wskazanej wyżej procedury można także złożyć wniosek do Rzecznika Praw Obywatelskich.',
		'a11y-procedura'
	);
	$markup .= block_paragraph( 'Strona internetowa Rzecznika Praw Obywatelskich: <a href="https://bip.brpo.gov.pl/">bip.brpo.gov.pl</a>.' );

	$markup .= block_heading( 'Dostępność architektoniczna', 2, 'a11y-architektura' );
	$markup .= block_paragraph( $todo( 'adres siedziby; dla każdej lokalizacji przygotuj osobny opis.' ) );
	$markup .= block_list(
		array(
			'Wejście do budynku i przejście przez obszary kontroli: ' . $todo( 'opis' ),
			'Korytarze, schody i windy: ' . $todo( 'opis' ),
			'Dostosowania (pochylnie, platformy, informacje głosowe, pętle indukcyjne): ' . $todo( 'opis' ),
			'Miejsca parkingowe dla osób z niepełnosprawnościami: ' . $todo( 'gdzie się znajdują i jak z nich korzystać' ),
			'Pies asystujący: ' . $todo( 'informacja o prawie wstępu z psem asystującym i ewentualnych uzasadnionych ograniczeniach' ),
			'Tłumacz polskiego języka migowego (PJM): ' . $todo( 'na miejscu, online albo informacja, że nie ma takiej możliwości' ),
		)
	);

	$markup .= block_heading( 'Aplikacje mobilne', 2, 'a11y-aplikacje' );
	$markup .= block_paragraph( $todo( 'wymień aplikacje mobilne z linkami do miejsca, z którego można je pobrać, albo usuń tę sekcję, jeśli podmiot ich nie udostępnia.' ) );

	return \rtrim( $markup ) . "\n";
}

/**
 * Accessibility statement following the EU model (Implementing Decision (EU) 2018/1523).
 *
 * @param array $data See template_data().
 * @return string Block markup.
 */
function template_eu( array $data ): string {
	$data = template_data( $data );

	$entity = '' !== $data['entity'] ? \esc_html( $data['entity'] ) : \esc_html__( '[FILL IN: name of the organisation]', 'isudev-library' );
	$name   = '' !== $data['site_name'] ? \esc_html( $data['site_name'] ) : \esc_html__( '[FILL IN: name of the website]', 'isudev-library' );
	$date   = '' !== $data['date'] ? \esc_html( $data['date'] ) : \esc_html__( '[FILL IN: date]', 'isudev-library' );
	$url    = '' !== $data['url'] ? '<a href="' . \esc_url( $data['url'] ) . '">' . $name . '</a>' : $name;

	$markup = block_paragraph( \esc_html__( '[FILL IN: replace every placeholder in square brackets and delete these notes before publishing. Review the statement at least once a year.]', 'isudev-library' ) );

	$markup .= block_paragraph(
		\sprintf(
			/* translators: 1: organisation name, 2: placeholder for the applicable law. */
			\esc_html__( '%1$s is committed to making its website accessible, in accordance with %2$s.', 'isudev-library' ),
			$entity,
			\esc_html__( '[FILL IN: the national law transposing Directive (EU) 2016/2102, or the European Accessibility Act]', 'isudev-library' )
		)
	);
	$markup .= block_paragraph(
		\sprintf(
			/* translators: %s: link to the website. */
			\esc_html__( 'This accessibility statement applies to %s.', 'isudev-library' ),
			$url
		)
	);

	$markup .= block_heading( \esc_html__( 'Compliance status', 'isudev-library' ) );
	$markup .= block_paragraph( \esc_html__( 'This website is partially compliant with EN 301 549 (WCAG 2.1 level AA) due to the non-compliances and exemptions listed below.', 'isudev-library' ) );
	$markup .= block_paragraph( \esc_html__( '[FILL IN: or "fully compliant" / "not compliant". When fully compliant, delete the next section.]', 'isudev-library' ) );

	$markup .= block_heading( \esc_html__( 'Non-accessible content', 'isudev-library' ) );
	$markup .= block_paragraph( \esc_html__( 'The content listed below is non-accessible for the following reasons:', 'isudev-library' ) );
	$markup .= block_list(
		array(
			\esc_html__( '[FILL IN: non-compliance with the requirements, e.g. some videos have no captions]', 'isudev-library' ),
			\esc_html__( '[FILL IN: content outside the scope of the applicable legislation, e.g. maps]', 'isudev-library' ),
		)
	);

	$markup .= block_heading( \esc_html__( 'Preparation of this accessibility statement', 'isudev-library' ) );
	$markup .= block_paragraph(
		\sprintf(
			/* translators: %s: date in YYYY-MM-DD format. */
			\esc_html__( 'This statement was prepared on %s. It is based on a self-assessment carried out by the organisation.', 'isudev-library' ),
			$date
		)
	);
	$markup .= block_paragraph( \esc_html__( '[FILL IN: if a third party audited the website, name it here instead of the self-assessment.]', 'isudev-library' ) );

	$markup .= block_heading( \esc_html__( 'Feedback and contact information', 'isudev-library' ) );
	$markup .= block_paragraph(
		\sprintf(
			/* translators: 1: contact person, 2: e-mail address, 3: phone number. */
			\esc_html__( 'If you find an accessibility problem on this website or need information in an accessible format, contact %1$s by e-mail at %2$s or by phone at %3$s.', 'isudev-library' ),
			\esc_html__( '[FILL IN: contact person]', 'isudev-library' ),
			\esc_html__( '[FILL IN: e-mail address]', 'isudev-library' ),
			\esc_html__( '[FILL IN: phone number]', 'isudev-library' )
		)
	);

	$markup .= block_heading( \esc_html__( 'Enforcement procedure', 'isudev-library' ) );
	$markup .= block_paragraph(
		\sprintf(
			/* translators: %s: placeholder for the enforcement body. */
			\esc_html__( 'If you are not satisfied with our response, you can contact %s.', 'isudev-library' ),
			\esc_html__( '[FILL IN: the national enforcement body and how to reach it]', 'isudev-library' )
		)
	);

	return \rtrim( $markup ) . "\n";
}
