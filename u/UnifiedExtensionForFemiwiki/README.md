# UnifiedExtensionForFemiwiki [![Github checks status]][github checks link] [![codecov.io status]][codecov.io link]

이것은 페미위키에 필요한 각종 기능들을 위한 종합적인 확장기능으로 다음을 제공합니다.

- 회원가입 시 "나는 페미니스트입니다"라는 문구를 입력 받게 함
- 사이트 푸터에 이용약관과 권리 침해 신고를 추가
- 문서 내용과 사이드바에서 `https://femiwiki.com`로 시작하는 링크, 혹은 사이트에 설정에 따라 같은 도메인으로 시작하는 링크를 내부 링크처럼 표시
- 모든 문서에 구글 태그 매니저 스크립트 추가
- 모든 문서의 관련 항목으로 해당 문서에서 가리키는 문서와 해당 문서를 가리키는 문서 추가
- 사용자 이메일을 페미위키 테마로 변경

Breaking change가 모든 릴리즈에서 있을 수 있습니다. 페미위키를 제외한 위키에서의 사용을 권하지 않습니다.

## 설치

1. 파일들을 다운로드 받아 `extensions/` 폴더 아래 `UnifiedExtensionForFemiwiki` 디렉토리에 넣으십시오.
2. LocalSettings.php에 다음을 추가하십시오.

```php
wfLoadExtension( 'UnifiedExtensionForFemiwiki' );
```

## Contributing

If you are interested in contributing to the code base, please see the document [How to Contribute].

---

The source code of _femiwiki/UnifiedExtensionForFemiwiki_ is primarily distributed under the terms
of the [GNU Affero General Public License v3.0] or any later version. See
[COPYRIGHT] for details.

[github checks status]: https://badgen.net/github/checks/femiwiki/UnifiedExtensionForFemiwiki
[github checks link]: https://github.com/femiwiki/UnifiedExtensionForFemiwiki/actions
[codecov.io status]: https://badgen.net/codecov/c/github/femiwiki/UnifiedExtensionForFemiwiki
[codecov.io link]: https://codecov.io/gh/femiwiki/UnifiedExtensionForFemiwiki
[how to contribute]: https://github.com/femiwiki/femiwiki/blob/main/how-to-contribute-to-extensions.md
[gnu affero general public license v3.0]: LICENSE
[copyright]: COPYRIGHT
