### Project Guidelines

#### Build & Configuration Instructions
- **Frontend Stack**: Use **TypeScript**, **React**, and **Tailwind CSS**.
- **UI Components**: Always prioritize using existing **Shadcn UI** components. If a new component is needed, ensure it follows the Shadcn/Radix UI patterns.
- **Backend Stack**: This is a Laravel application using **Inertia.js** to bridge the backend and frontend.
- **Code Standards**: Adhere to modern and newest code standards (PHP 8.2+, React 18+, TypeScript). Use clean code principles, type safety, and functional components where possible.

#### Testing & Environment Information
- **Local Server**: The application is hosted at [http://127.0.0.1:8000](http://127.0.0.1:8000).
- **Environment**: Ensure `.env` is properly configured. Use `php artisan serve` or the provided `npm run dev` (which uses concurrently to run the server and Vite).

#### Development Principles
- **Professionalism**: Act as a professional senior developer. Write maintainable, well-documented, and efficient code.
- **Communication**: If you are not 100% sure about a requirement or implementation detail, **ask for clarification**. Do not hallucinate or make assumptions.
- **Cleanup**: Always delete any temporary or additional files created during development, except for `.junie/guidelines.md`.

#### Useful Commands
- `composer setup`: Runs installation, migrations, and builds frontend.
- `php artisan serve`: Starts the Laravel development server.
- `npm run dev`: Starts the Vite development server and Laravel server concurrently.
- `php artisan test`: Runs the test suite.
